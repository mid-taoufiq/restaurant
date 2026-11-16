<?php

namespace App\Tests\Unit;

use App\Entity\CustomerOrder;
use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Preparation;
use App\Service\RestaurantService;
use App\Service\UnitConverter;
use PHPUnit\Framework\TestCase;

class RestaurantRulesTest extends TestCase
{
    private RestaurantService $service;

    protected function setUp(): void
    {
        $this->service = new RestaurantService(new UnitConverter());
    }

    public function testDishCostAndMarginWithPreparation(): void
    {
        $flour = new Ingredient('Flour', 'kg', 10.0, 2.0, 2.00); // 2.00 EUR/kg
        $mozzarella = new Ingredient('Mozzarella', 'kg', 5.0, 1.0, 10.00); // 10.00 EUR/kg

        // Preparation: 10 portions using 1kg Flour (cost = 2.00 EUR total => 0.20 EUR/portion)
        $dough = (new Preparation('Pizza Dough', 10.0))
            ->addItem($flour, 1.0, 'kg');

        // Dish: 1 portion dough (0.20 EUR) + 150g Mozzarella (1.50 EUR) = 1.70 EUR cost
        $pizza = (new Dish('Margherita', 'main', 12.00))
            ->addPreparation($dough, 1.0)
            ->addIngredient($mozzarella, 150.0, 'g');

        $marginData = $this->service->calculateDishMargin($pizza);
        $this->assertSame(1.70, $marginData['cost']);
        $this->assertSame(10.30, $marginData['margin']);
        $this->assertSame(85.8, $marginData['marginPercent']);
    }

    public function testServingDishDecrementsStockAndSnapshotsCost(): void
    {
        $beef = new Ingredient('Beef Tenderloin', 'kg', 1.0, 0.2, 20.00); // 1.0 kg in stock
        $steak = (new Dish('Steak', 'main', 25.00))
            ->addIngredient($beef, 200.0, 'g'); // 0.2 kg per dish => 4.00 EUR cost

        $order = new CustomerOrder(3);
        $item = $order->addItem($steak, 2); // 2 portions => 0.4 kg

        $this->service->serveOrderItem($item);

        $this->assertSame('served', $item->getStatus());
        $this->assertSame(0.6, $beef->getCurrentStock());
        $this->assertSame(4.00, $item->getUnitCostSnapshot());

        // If supplier price increases later, already served item keeps its historical cost snapshot
        $this->service->recordPurchase($beef, 5.0, 'kg', 30.00, 'Price increase');
        $this->assertSame(4.00, $item->getUnitCostSnapshot());
        $this->assertSame(6.00, $this->service->calculateDishCost($steak));
    }

    public function testDishBecomesUnavailableWhenIngredientIsMissing(): void
    {
        $salmon = new Ingredient('Salmon', 'kg', 0.15, 0.5, 22.00); // Only 150g left
        $dish = (new Dish('Grilled Salmon', 'main', 21.00))
            ->addIngredient($salmon, 200.0, 'g'); // Requires 200g

        $this->assertFalse($this->service->isDishAvailable($dish));
        $this->assertSame(['Salmon'], $this->service->getMissingIngredientsForDish($dish));
    }

    public function testStockCannotBecomeNegativeOnServiceOrLoss(): void
    {
        $butter = new Ingredient('Butter', 'kg', 0.1, 0.1, 9.00); // 100g available
        $this->expectException(\DomainException::class);
        $this->service->recordLoss($butter, 250.0, 'g', 'Expired package');
    }

    public function testPhysicalInventoryCalculatesDiscrepancy(): void
    {
        $oliveOil = new Ingredient('Olive Oil', 'L', 10.0, 2.0, 7.50);
        $result = $this->service->performPhysicalInventory($oliveOil, 9.25, 'End of week count');

        $this->assertSame(10.0, $result['theoretical']);
        $this->assertSame(9.25, $result['counted']);
        $this->assertSame(-0.75, $result['discrepancy']);
        $this->assertSame(9.25, $oliveOil->getCurrentStock());
        $this->assertSame('inventory_adjustment', $result['movement']->getType());
    }
}
