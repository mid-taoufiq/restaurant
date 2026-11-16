<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\OrderItem;
use App\Entity\Preparation;
use App\Entity\StockMovement;
use Doctrine\ORM\EntityManagerInterface;

class RestaurantService
{
    public function __construct(
        private UnitConverter $unitConverter,
        private ?EntityManagerInterface $em = null
    ) {}

    /**
     * Returns required quantities per ingredient in the ingredient's own stock unit.
     * @return array<int|string, array{ingredient: Ingredient, quantity: float}>
     */
    public function getRequiredIngredientsForDish(Dish $dish, int $portions = 1): array
    {
        $requirements = [];

        foreach ($dish->getDishIngredients() as $di) {
            $ing = $di->getIngredient();
            $key = $ing->getId() ?? spl_object_id($ing);
            $converted = $this->unitConverter->convert($di->getQuantity() * $portions, $di->getUnit(), $ing->getUnit());
            if (!isset($requirements[$key])) {
                $requirements[$key] = ['ingredient' => $ing, 'quantity' => 0.0];
            }
            $requirements[$key]['quantity'] += $converted;
        }

        foreach ($dish->getDishPreparations() as $dp) {
            $prep = $dp->getPreparation();
            $ratio = ($dp->getPortions() * $portions) / $prep->getYieldPortions();
            foreach ($prep->getItems() as $pi) {
                $ing = $pi->getIngredient();
                $key = $ing->getId() ?? spl_object_id($ing);
                $converted = $this->unitConverter->convert($pi->getQuantity() * $ratio, $pi->getUnit(), $ing->getUnit());
                if (!isset($requirements[$key])) {
                    $requirements[$key] = ['ingredient' => $ing, 'quantity' => 0.0];
                }
                $requirements[$key]['quantity'] += $converted;
            }
        }

        return $requirements;
    }

    public function calculatePreparationCostPerPortion(Preparation $prep): float
    {
        $total = 0.0;
        foreach ($prep->getItems() as $pi) {
            $ing = $pi->getIngredient();
            $qtyInStockUnit = $this->unitConverter->convert($pi->getQuantity(), $pi->getUnit(), $ing->getUnit());
            $total += $qtyInStockUnit * $ing->getUnitCost();
        }
        return round($total / $prep->getYieldPortions(), 4);
    }

    public function calculateDishCost(Dish $dish): float
    {
        $cost = 0.0;
        foreach ($this->getRequiredIngredientsForDish($dish, 1) as $req) {
            $cost += $req['quantity'] * $req['ingredient']->getUnitCost();
        }
        return round($cost, 2);
    }

    /**
     * @return array{price: float, cost: float, margin: float, marginPercent: float}
     */
    public function calculateDishMargin(Dish $dish): array
    {
        $price = $dish->getPrice();
        $cost = $this->calculateDishCost($dish);
        $margin = round($price - $cost, 2);
        $marginPercent = $price > 0 ? round(($margin / $price) * 100, 1) : 0.0;

        return [
            'price' => $price,
            'cost' => $cost,
            'margin' => $margin,
            'marginPercent' => $marginPercent,
        ];
    }

    /**
     * @return string[] Names of missing ingredients
     */
    public function getMissingIngredientsForDish(Dish $dish, int $portions = 1): array
    {
        $missing = [];
        foreach ($this->getRequiredIngredientsForDish($dish, $portions) as $req) {
            $ing = $req['ingredient'];
            if ($ing->getCurrentStock() + 1e-6 < $req['quantity']) {
                $missing[] = $ing->getName();
            }
        }
        return $missing;
    }

    public function isDishAvailable(Dish $dish, int $portions = 1): bool
    {
        if (!$dish->isActive()) {
            return false;
        }
        return count($this->getMissingIngredientsForDish($dish, $portions)) === 0;
    }

    public function serveOrderItem(OrderItem $item): void
    {
        if ($item->getStatus() === 'served') {
            return;
        }

        $dish = $item->getDish();
        $portions = $item->getQuantity();
        $requirements = $this->getRequiredIngredientsForDish($dish, $portions);

        foreach ($requirements as $req) {
            $ing = $req['ingredient'];
            if ($ing->getCurrentStock() + 1e-6 < $req['quantity']) {
                throw new \DomainException(sprintf(
                    'Cannot serve "%s": insufficient stock for ingredient "%s" (required: %.3f %s, available: %.3f %s).',
                    $dish->getName(),
                    $ing->getName(),
                    $req['quantity'],
                    $ing->getUnit(),
                    $ing->getCurrentStock(),
                    $ing->getUnit()
                ));
            }
        }

        foreach ($requirements as $req) {
            $ing = $req['ingredient'];
            $delta = -round($req['quantity'], 4);
            $newStock = max(0.0, round($ing->getCurrentStock() + $delta, 4));
            $ing->setCurrentStock($newStock);

            $movement = new StockMovement(
                $ing,
                $delta,
                'service_consumption',
                sprintf('Served %dx %s (Table #%d)', $portions, $dish->getName(), $item->getCustomerOrder()->getTableNumber()),
                $ing->getUnitCost()
            );
            $this->em?->persist($movement);
        }

        // Snapshot unit cost at time of service so future ingredient price changes do not alter past served dishes
        $item->setUnitCostSnapshot($this->calculateDishCost($dish));
        $item->setStatus('served');
        $this->em?->flush();
    }

    public function recordPurchase(Ingredient $ingredient, float $quantity, string $unit, ?float $newUnitCost = null, string $reason = 'Supplier purchase'): StockMovement
    {
        if ($quantity <= 0) {
            throw new \DomainException('Purchase quantity must be strictly positive.');
        }

        $addedInStockUnit = $this->unitConverter->convert($quantity, $unit, $ingredient->getUnit());
        $ingredient->setCurrentStock(round($ingredient->getCurrentStock() + $addedInStockUnit, 4));

        if ($newUnitCost !== null && $newUnitCost > 0) {
            $ingredient->setUnitCost($newUnitCost);
        }

        $movement = new StockMovement($ingredient, $addedInStockUnit, 'purchase', $reason, $ingredient->getUnitCost());
        $this->em?->persist($movement);
        $this->em?->flush();

        return $movement;
    }

    public function recordLoss(Ingredient $ingredient, float $quantity, string $unit, string $reason = 'Kitchen loss / expiration'): StockMovement
    {
        if ($quantity <= 0) {
            throw new \DomainException('Loss quantity must be strictly positive.');
        }

        $lossInStockUnit = $this->unitConverter->convert($quantity, $unit, $ingredient->getUnit());
        if ($lossInStockUnit > $ingredient->getCurrentStock() + 1e-6) {
            throw new \DomainException(sprintf(
                'Stock cannot become negative for "%s" (available: %.3f %s, loss: %.3f %s).',
                $ingredient->getName(),
                $ingredient->getCurrentStock(),
                $ingredient->getUnit(),
                $lossInStockUnit,
                $ingredient->getUnit()
            ));
        }

        $ingredient->setCurrentStock(max(0.0, round($ingredient->getCurrentStock() - $lossInStockUnit, 4)));
        $movement = new StockMovement($ingredient, -$lossInStockUnit, 'loss', $reason, $ingredient->getUnitCost());
        $this->em?->persist($movement);
        $this->em?->flush();

        return $movement;
    }

    /**
     * @return array{theoretical: float, counted: float, discrepancy: float, movement: StockMovement}
     */
    public function performPhysicalInventory(Ingredient $ingredient, float $countedStock, string $reason = 'Physical inventory count'): array
    {
        if ($countedStock < 0) {
            throw new \DomainException('Counted physical stock cannot be negative.');
        }

        $theoretical = $ingredient->getCurrentStock();
        $discrepancy = round($countedStock - $theoretical, 4);
        $ingredient->setCurrentStock(round($countedStock, 4));

        $movement = new StockMovement(
            $ingredient,
            $discrepancy,
            'inventory_adjustment',
            sprintf('%s (theoretical: %.3f, counted: %.3f, discrepancy: %+.3f %s)', $reason, $theoretical, $countedStock, $discrepancy, $ingredient->getUnit()),
            $ingredient->getUnitCost()
        );
        $this->em?->persist($movement);
        $this->em?->flush();

        return [
            'theoretical' => $theoretical,
            'counted' => $countedStock,
            'discrepancy' => $discrepancy,
            'movement' => $movement,
        ];
    }
}
