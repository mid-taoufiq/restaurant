<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Menu;
use App\Entity\OrderItem;
use App\Entity\Preparation;
use App\Entity\StockMovement;
use App\Entity\Supplier;
use App\Service\RestaurantService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RestaurantController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function menu(EntityManagerInterface $em, RestaurantService $restaurantService): Response
    {
        $dishes = $em->getRepository(Dish::class)->findBy(['isActive' => true], ['category' => 'ASC', 'name' => 'ASC']);
        $menus = $em->getRepository(Menu::class)->findAll();

        $dishCards = [];
        foreach ($dishes as $dish) {
            $dishCards[] = [
                'dish' => $dish,
                'available' => $restaurantService->isDishAvailable($dish),
                'missing' => $restaurantService->getMissingIngredientsForDish($dish),
                'allergens' => $dish->getAllergens(),
            ];
        }

        return $this->render('restaurant/menu.html.twig', [
            'dishCards' => $dishCards,
            'menus' => $menus,
        ]);
    }

    #[Route('/waiter', name: 'app_waiter', methods: ['GET', 'POST'])]
    public function waiter(Request $request, EntityManagerInterface $em, RestaurantService $restaurantService): Response
    {
        $dishes = $em->getRepository(Dish::class)->findBy(['isActive' => true], ['category' => 'ASC', 'name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $tableNumber = max(1, (int) $request->request->get('table_number', 1));
            $quantities = $request->request->all('quantities');
            $order = new CustomerOrder($tableNumber, $this->getUser()?->getUserIdentifier() ?? 'Waiter');
            $addedCount = 0;

            foreach ($dishes as $dish) {
                $qty = (int) ($quantities[$dish->getId()] ?? 0);
                if ($qty > 0) {
                    if (!$restaurantService->isDishAvailable($dish, $qty)) {
                        $this->addFlash('error', sprintf('Cannot order %dx "%s": insufficient ingredient stock.', $qty, $dish->getName()));
                        return $this->redirectToRoute('app_waiter');
                    }
                    $order->addItem($dish, $qty);
                    $addedCount += $qty;
                }
            }

            if ($addedCount > 0) {
                $em->persist($order);
                $em->flush();
                $this->addFlash('success', sprintf('Order for Table #%d (%d items) sent to the kitchen!', $tableNumber, $addedCount));
            } else {
                $this->addFlash('error', 'Please select at least one dish quantity.');
            }

            return $this->redirectToRoute('app_waiter');
        }

        $availability = [];
        foreach ($dishes as $dish) {
            $availability[$dish->getId()] = [
                'available' => $restaurantService->isDishAvailable($dish),
                'missing' => $restaurantService->getMissingIngredientsForDish($dish),
            ];
        }

        $openOrders = $em->getRepository(CustomerOrder::class)->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('restaurant/waiter.html.twig', [
            'dishes' => $dishes,
            'availability' => $availability,
            'openOrders' => $openOrders,
        ]);
    }

    #[Route('/kitchen', name: 'app_kitchen', methods: ['GET'])]
    public function kitchen(EntityManagerInterface $em): Response
    {
        $items = $em->getRepository(OrderItem::class)->findBy([], ['id' => 'DESC'], 50);

        return $this->render('restaurant/kitchen.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/kitchen/item/{id}/status', name: 'app_kitchen_status', methods: ['POST'])]
    public function updateKitchenItemStatus(OrderItem $item, Request $request, EntityManagerInterface $em, RestaurantService $restaurantService): Response
    {
        $newStatus = (string) $request->request->get('status', 'preparing');

        try {
            if ($newStatus === 'served') {
                $restaurantService->serveOrderItem($item);
                $this->addFlash('success', sprintf('Served %dx "%s" — ingredient stocks automatically decremented.', $item->getQuantity(), $item->getDish()->getName()));
            } else {
                $item->setStatus($newStatus);
                $em->flush();
                $this->addFlash('success', sprintf('Updated "%s" status to "%s".', $item->getDish()->getName(), $newStatus));
            }
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_kitchen');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(EntityManagerInterface $em, RestaurantService $restaurantService): Response
    {
        $dishes = $em->getRepository(Dish::class)->findAll();
        $ingredients = $em->getRepository(Ingredient::class)->findAll();
        $orderItems = $em->getRepository(OrderItem::class)->findBy(['status' => 'served']);

        $margins = [];
        foreach ($dishes as $dish) {
            $margins[] = [
                'dish' => $dish,
                'metrics' => $restaurantService->calculateDishMargin($dish),
                'available' => $restaurantService->isDishAvailable($dish),
            ];
        }

        $salesByDish = [];
        foreach ($orderItems as $item) {
            $dId = $item->getDish()->getId();
            if (!isset($salesByDish[$dId])) {
                $salesByDish[$dId] = [
                    'dishName' => $item->getDish()->getName(),
                    'quantitySold' => 0,
                    'revenue' => 0.0,
                    'historicalCost' => 0.0,
                ];
            }
            $salesByDish[$dId]['quantitySold'] += $item->getQuantity();
            $salesByDish[$dId]['revenue'] += $item->getQuantity() * $item->getUnitPrice();
            $unitCost = $item->getUnitCostSnapshot() ?? $restaurantService->calculateDishCost($item->getDish());
            $salesByDish[$dId]['historicalCost'] += $item->getQuantity() * $unitCost;
        }
        usort($salesByDish, fn(array $a, array $b) => $b['quantitySold'] <=> $a['quantitySold']);

        $lowStockIngredients = array_filter($ingredients, fn(Ingredient $i) => $i->isLowStock());

        return $this->render('restaurant/dashboard.html.twig', [
            'margins' => $margins,
            'bestSellers' => $salesByDish,
            'lowStockIngredients' => $lowStockIngredients,
        ]);
    }

    #[Route('/backoffice', name: 'app_backoffice', methods: ['GET', 'POST'])]
    public function backoffice(Request $request, EntityManagerInterface $em, RestaurantService $restaurantService): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action');

            try {
                if ($action === 'purchase') {
                    $ing = $em->find(Ingredient::class, (int) $request->request->get('ingredient_id'));
                    if ($ing) {
                        $qty = (float) $request->request->get('quantity');
                        $unit = (string) $request->request->get('unit', $ing->getUnit());
                        $cost = (float) $request->request->get('unit_cost', $ing->getUnitCost());
                        $restaurantService->recordPurchase($ing, $qty, $unit, $cost, 'Back-office supplier purchase');
                        $this->addFlash('success', sprintf('Added %.2f %s of %s to stock.', $qty, $unit, $ing->getName()));
                    }
                } elseif ($action === 'loss') {
                    $ing = $em->find(Ingredient::class, (int) $request->request->get('ingredient_id'));
                    if ($ing) {
                        $qty = (float) $request->request->get('quantity');
                        $unit = (string) $request->request->get('unit', $ing->getUnit());
                        $reason = (string) $request->request->get('reason', 'Kitchen waste / breakage');
                        $restaurantService->recordLoss($ing, $qty, $unit, $reason);
                        $this->addFlash('success', sprintf('Recorded loss of %.2f %s for %s (%s).', $qty, $unit, $ing->getName(), $reason));
                    }
                } elseif ($action === 'inventory') {
                    $ing = $em->find(Ingredient::class, (int) $request->request->get('ingredient_id'));
                    if ($ing) {
                        $counted = (float) $request->request->get('counted_stock');
                        $res = $restaurantService->performPhysicalInventory($ing, $counted, 'Manager physical count');
                        $this->addFlash('success', sprintf(
                            'Inventory updated for %s: theoretical %.2f %s, counted %.2f %s (discrepancy: %+.2f %s).',
                            $ing->getName(),
                            $res['theoretical'],
                            $ing->getUnit(),
                            $res['counted'],
                            $ing->getUnit(),
                            $res['discrepancy'],
                            $ing->getUnit()
                        ));
                    }
                } elseif ($action === 'create_dish') {
                    $dish = new Dish(
                        (string) $request->request->get('name'),
                        (string) $request->request->get('category', 'main'),
                        (float) $request->request->get('price', 12.0)
                    );
                    $ing = $em->find(Ingredient::class, (int) $request->request->get('ingredient_id'));
                    $qty = (float) $request->request->get('ingredient_qty', 100);
                    if ($ing && $qty > 0) {
                        $dish->addIngredient($ing, $qty, $ing->getUnit());
                    }
                    $em->persist($dish);
                    $em->flush();
                    $this->addFlash('success', sprintf('Created new dish "%s".', $dish->getName()));
                }
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_backoffice');
        }

        return $this->render('restaurant/backoffice.html.twig', [
            'ingredients' => $em->getRepository(Ingredient::class)->findBy([], ['name' => 'ASC']),
            'suppliers' => $em->getRepository(Supplier::class)->findAll(),
            'preparations' => $em->getRepository(Preparation::class)->findAll(),
            'dishes' => $em->getRepository(Dish::class)->findAll(),
            'menus' => $em->getRepository(Menu::class)->findAll(),
            'movements' => $em->getRepository(StockMovement::class)->findBy([], ['id' => 'DESC'], 20),
        ]);
    }
}
