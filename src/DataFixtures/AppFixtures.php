<?php

namespace App\DataFixtures;

use App\Entity\CustomerOrder;
use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Menu;
use App\Entity\Preparation;
use App\Entity\StockMovement;
use App\Entity\Supplier;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Users (Manager, Waiter, Kitchen)
        $usersData = [
            ['manager@restaurant.local', 'Karim Bennani (Manager)', ['ROLE_MANAGER']],
            ['waiter@restaurant.local', 'Sofia Alaoui (Waiter)', ['ROLE_WAITER']],
            ['kitchen@restaurant.local', 'Youssef Tazi (Chef)', ['ROLE_KITCHEN']],
        ];
        foreach ($usersData as [$email, $name, $roles]) {
            $u = new User($email, $name, $roles);
            $u->setPassword($this->hasher->hashPassword($u, 'password'));
            $manager->persist($u);
        }

        // 2. Suppliers (5 suppliers)
        $suppliers = [];
        $suppliersData = [
            'primeur' => ['Maison Primeur Bio', 'contact@primeurbio.ma', '+212 522 10 20 30'],
            'boucherie' => ['Boucherie & Volaille du Terroir', 'commandes@terroir-viandes.ma', '+212 522 40 50 60'],
            'maree' => ['Marée Fraîche Atlantique', 'ventes@maree-atlantique.ma', '+212 522 70 80 90'],
            'cremerie' => ['Crémerie des Alpes', 'info@cremerie-alpes.ma', '+212 522 11 22 33'],
            'epicerie' => ['Épicerie Fine Méditerranée', 'pro@epicerie-med.ma', '+212 522 44 55 66'],
        ];
        foreach ($suppliersData as $key => [$name, $email, $phone]) {
            $s = new Supplier($name, $email, $phone);
            $manager->persist($s);
            $suppliers[$key] = $s;
        }

        // 3. Ingredients (50 realistic ingredients)
        $ingredientsList = [
            // Primeur (14)
            ['Tomatoes', 'kg', 25.0, 5.0, 1.80, 'primeur', null, []],
            ['Red Onions', 'kg', 15.0, 3.0, 1.40, 'primeur', null, []],
            ['Garlic', 'kg', 4.0, 1.0, 4.50, 'primeur', null, []],
            ['Potatoes', 'kg', 40.0, 10.0, 1.10, 'primeur', null, []],
            ['Carrots', 'kg', 18.0, 4.0, 1.30, 'primeur', null, []],
            ['Romaine Lettuce', 'piece', 24.0, 6.0, 0.90, 'primeur', null, []],
            ['Fresh Basil', 'g', 800.0, 200.0, 0.02, 'primeur', null, []],
            ['Lemons', 'kg', 8.0, 2.0, 2.20, 'primeur', null, []],
            ['Button Mushrooms', 'kg', 6.0, 2.0, 5.50, 'primeur', null, []],
            ['Zucchini', 'kg', 10.0, 3.0, 1.90, 'primeur', null, []],
            ['Eggplant', 'kg', 8.0, 2.0, 2.10, 'primeur', null, []],
            ['Bell Peppers', 'kg', 9.0, 2.0, 2.40, 'primeur', null, []],
            ['Celery Stalks', 'kg', 3.5, 1.0, 2.00, 'primeur', null, ['celery']],
            ['Fresh Mint', 'g', 600.0, 150.0, 0.015, 'primeur', null, []],

            // Boucherie (8)
            ['Beef Tenderloin', 'kg', 9.0, 3.0, 24.00, 'boucherie', null, []],
            ['Ground Beef', 'kg', 12.0, 4.0, 11.50, 'boucherie', null, []],
            ['Chicken Breast', 'kg', 14.0, 4.0, 8.90, 'boucherie', null, []],
            ['Beef Bones', 'kg', 10.0, 2.0, 3.50, 'boucherie', null, []],
            ['Duck Breast', 'kg', 4.5, 2.0, 18.50, 'boucherie', null, []],
            ['Lamb Shank', 'kg', 6.0, 2.0, 16.00, 'boucherie', null, []],
            ['Smoked Turkey Bacon', 'kg', 3.0, 1.0, 12.00, 'boucherie', null, []],
            ['Veal Escalope', 'kg', 5.0, 2.0, 19.00, 'boucherie', null, []],

            // Marée (6)
            ['Salmon Fillet', 'kg', 2.2, 3.0, 21.00, 'maree', null, ['fish']], // Low stock example!
            ['Sea Bass Fillet', 'kg', 5.5, 2.0, 19.50, 'maree', null, ['fish']],
            ['Tiger Prawns', 'kg', 4.0, 1.5, 23.00, 'maree', null, ['crustaceans']],
            ['Calamari Rings', 'kg', 5.0, 1.5, 13.50, 'maree', null, ['molluscs']],
            ['Mussels', 'kg', 8.0, 2.0, 6.50, 'maree', null, ['molluscs']],
            ['Anchovy Fillets', 'g', 900.0, 200.0, 0.03, 'maree', null, ['fish']],

            // Crémerie (10)
            ['Whole Milk', 'L', 20.0, 6.0, 1.15, 'cremerie', '3250390000105', ['milk']],
            ['Heavy Cream', 'L', 12.0, 4.0, 3.80, 'cremerie', '3155250349793', ['milk']],
            ['Unsalted Butter', 'kg', 8.0, 2.5, 9.20, 'cremerie', '3155250358788', ['milk']],
            ['Mozzarella Fior di Latte', 'kg', 7.0, 2.5, 10.50, 'cremerie', '8000430139037', ['milk']],
            ['Parmigiano Reggiano', 'kg', 1.8, 2.0, 22.00, 'cremerie', '8010721000054', ['milk']], // Low stock example!
            ['Goat Cheese Log', 'kg', 3.5, 1.0, 14.00, 'cremerie', null, ['milk']],
            ['Mascarpone', 'kg', 4.0, 1.5, 11.00, 'cremerie', null, ['milk']],
            ['Free-Range Eggs', 'piece', 120.0, 30.0, 0.25, 'cremerie', null, ['eggs']],
            ['Cheddar Slices', 'kg', 4.0, 1.0, 11.50, 'cremerie', null, ['milk']],
            ['Greek Yogurt', 'kg', 5.0, 1.5, 4.20, 'cremerie', null, ['milk']],

            // Épicerie (12)
            ['Wheat Flour T55', 'kg', 30.0, 8.0, 1.20, 'epicerie', '3228857000166', ['gluten']],
            ['Spaghetti Pasta', 'kg', 18.0, 5.0, 2.10, 'epicerie', '8076800195057', ['gluten']],
            ['Arborio Rice', 'kg', 12.0, 3.0, 3.40, 'epicerie', null, []],
            ['Extra Virgin Olive Oil', 'L', 15.0, 4.0, 7.50, 'epicerie', '3068320115160', []],
            ['Dijon Mustard', 'kg', 3.0, 1.0, 4.80, 'epicerie', '3036810202621', ['mustard']],
            ['Balsamic Vinegar', 'L', 4.0, 1.0, 5.20, 'epicerie', null, ['sulphites']],
            ['Dark Chocolate 70%', 'kg', 1.2, 2.0, 13.50, 'epicerie', '3046920022651', ['milk', 'soybeans']], // Low stock example!
            ['Hazelnut Spread (Nutella)', 'kg', 3.0, 1.0, 8.50, 'epicerie', '3017620422003', ['milk', 'nuts', 'soybeans']],
            ['Caster Sugar', 'kg', 20.0, 5.0, 1.30, 'epicerie', null, []],
            ['Burger Buns', 'piece', 45.0, 15.0, 0.45, 'epicerie', null, ['gluten', 'sesame-seeds']],
            ['Ladyfinger Biscuits', 'kg', 3.0, 1.0, 6.80, 'epicerie', null, ['gluten', 'eggs']],
            ['Mineral Water 500mL', 'piece', 80.0, 20.0, 0.60, 'epicerie', '3068320114453', []],
        ];

        $ing = [];
        foreach ($ingredientsList as [$name, $unit, $stock, $min, $cost, $supKey, $barcode, $allergens]) {
            $entity = new Ingredient($name, $unit, $stock, $min, $cost, $suppliers[$supKey], $barcode, $allergens);
            $manager->persist($entity);
            $ing[$name] = $entity;

            // Initial stock purchase movement
            $manager->persist(new StockMovement($entity, $stock, 'purchase', 'Initial inventory opening stock', $cost));
        }

        // 4. Base Preparations (6 reusable kitchen preparations)
        $prepTomato = (new Preparation('Classic Tomato Sauce', 10.0))
            ->addItem($ing['Tomatoes'], 2.0, 'kg')
            ->addItem($ing['Red Onions'], 0.3, 'kg')
            ->addItem($ing['Garlic'], 0.05, 'kg')
            ->addItem($ing['Extra Virgin Olive Oil'], 100.0, 'mL')
            ->addItem($ing['Fresh Basil'], 30.0, 'g');
        $manager->persist($prepTomato);

        $prepBechamel = (new Preparation('Traditional Béchamel', 10.0))
            ->addItem($ing['Whole Milk'], 1.0, 'L')
            ->addItem($ing['Unsalted Butter'], 100.0, 'g')
            ->addItem($ing['Wheat Flour T55'], 100.0, 'g');
        $manager->persist($prepBechamel);

        $prepDough = (new Preparation('Artisan Pizza Dough', 8.0))
            ->addItem($ing['Wheat Flour T55'], 1.2, 'kg')
            ->addItem($ing['Extra Virgin Olive Oil'], 60.0, 'mL');
        $manager->persist($prepDough);

        $prepBeefStock = (new Preparation('Rich Beef Stock', 15.0))
            ->addItem($ing['Beef Bones'], 2.0, 'kg')
            ->addItem($ing['Carrots'], 0.5, 'kg')
            ->addItem($ing['Celery Stalks'], 0.3, 'kg')
            ->addItem($ing['Red Onions'], 0.4, 'kg');
        $manager->persist($prepBeefStock);

        $prepVinaigrette = (new Preparation('House Mustard Vinaigrette', 20.0))
            ->addItem($ing['Extra Virgin Olive Oil'], 400.0, 'mL')
            ->addItem($ing['Balsamic Vinegar'], 150.0, 'mL')
            ->addItem($ing['Dijon Mustard'], 80.0, 'g');
        $manager->persist($prepVinaigrette);

        $prepPastryCream = (new Preparation('Vanilla Pastry Cream', 10.0))
            ->addItem($ing['Whole Milk'], 1.0, 'L')
            ->addItem($ing['Free-Range Eggs'], 6.0, 'piece')
            ->addItem($ing['Caster Sugar'], 200.0, 'g')
            ->addItem($ing['Wheat Flour T55'], 80.0, 'g');
        $manager->persist($prepPastryCream);

        // 5. Dishes (20 seasonal restaurant dishes)
        $dishes = [];

        // Starters (5)
        $dishes['caesar'] = (new Dish('Classic Caesar Salad', 'starter', 9.50))
            ->addIngredient($ing['Romaine Lettuce'], 1.0, 'piece')
            ->addIngredient($ing['Chicken Breast'], 100.0, 'g')
            ->addIngredient($ing['Parmigiano Reggiano'], 30.0, 'g')
            ->addIngredient($ing['Anchovy Fillets'], 15.0, 'g')
            ->addPreparation($prepVinaigrette, 1.0);

        $dishes['bruschetta'] = (new Dish('Tomato & Basil Bruschetta', 'starter', 7.50))
            ->addIngredient($ing['Tomatoes'], 200.0, 'g')
            ->addIngredient($ing['Fresh Basil'], 10.0, 'g')
            ->addIngredient($ing['Garlic'], 5.0, 'g')
            ->addIngredient($ing['Extra Virgin Olive Oil'], 20.0, 'mL');

        $dishes['goat_salad'] = (new Dish('Warm Goat Cheese Salad', 'starter', 10.50))
            ->addIngredient($ing['Romaine Lettuce'], 1.0, 'piece')
            ->addIngredient($ing['Goat Cheese Log'], 90.0, 'g')
            ->addPreparation($prepVinaigrette, 1.0);

        $dishes['calamari'] = (new Dish('Crispy Fried Calamari', 'starter', 11.00))
            ->addIngredient($ing['Calamari Rings'], 180.0, 'g')
            ->addIngredient($ing['Wheat Flour T55'], 50.0, 'g')
            ->addIngredient($ing['Lemons'], 80.0, 'g');

        $dishes['garlic_prawns'] = (new Dish('Sautéed Garlic Tiger Prawns', 'starter', 13.50))
            ->addIngredient($ing['Tiger Prawns'], 160.0, 'g')
            ->addIngredient($ing['Garlic'], 10.0, 'g')
            ->addIngredient($ing['Unsalted Butter'], 25.0, 'g');

        // Mains (8)
        $dishes['margherita'] = (new Dish('Pizza Margherita D.O.P.', 'main', 13.00))
            ->addPreparation($prepDough, 1.0)
            ->addPreparation($prepTomato, 1.0)
            ->addIngredient($ing['Mozzarella Fior di Latte'], 125.0, 'g')
            ->addIngredient($ing['Fresh Basil'], 8.0, 'g');

        $dishes['bolognese'] = (new Dish('Spaghetti alla Bolognese', 'main', 14.50))
            ->addIngredient($ing['Spaghetti Pasta'], 140.0, 'g')
            ->addIngredient($ing['Ground Beef'], 150.0, 'g')
            ->addIngredient($ing['Parmigiano Reggiano'], 25.0, 'g')
            ->addPreparation($prepTomato, 1.0);

        $dishes['tenderloin'] = (new Dish('Grilled Beef Tenderloin & Jus', 'main', 26.00))
            ->addIngredient($ing['Beef Tenderloin'], 220.0, 'g')
            ->addIngredient($ing['Potatoes'], 250.0, 'g')
            ->addIngredient($ing['Unsalted Butter'], 30.0, 'g')
            ->addPreparation($prepBeefStock, 1.0);

        $dishes['salmon'] = (new Dish('Roasted Atlantic Salmon', 'main', 21.00))
            ->addIngredient($ing['Salmon Fillet'], 200.0, 'g')
            ->addIngredient($ing['Zucchini'], 150.0, 'g')
            ->addIngredient($ing['Lemons'], 60.0, 'g')
            ->addIngredient($ing['Extra Virgin Olive Oil'], 20.0, 'mL');

        $dishes['burger'] = (new Dish('House Bistro Cheeseburger', 'main', 16.00))
            ->addIngredient($ing['Burger Buns'], 1.0, 'piece')
            ->addIngredient($ing['Ground Beef'], 180.0, 'g')
            ->addIngredient($ing['Cheddar Slices'], 40.0, 'g')
            ->addIngredient($ing['Smoked Turkey Bacon'], 30.0, 'g')
            ->addIngredient($ing['Potatoes'], 200.0, 'g');

        $dishes['risotto'] = (new Dish('Wild Mushroom Risotto', 'main', 17.50))
            ->addIngredient($ing['Arborio Rice'], 120.0, 'g')
            ->addIngredient($ing['Button Mushrooms'], 150.0, 'g')
            ->addIngredient($ing['Parmigiano Reggiano'], 35.0, 'g')
            ->addIngredient($ing['Unsalted Butter'], 30.0, 'g');

        $dishes['lasagna'] = (new Dish('Oven-Baked Beef Lasagna', 'main', 15.50))
            ->addIngredient($ing['Ground Beef'], 160.0, 'g')
            ->addIngredient($ing['Mozzarella Fior di Latte'], 80.0, 'g')
            ->addPreparation($prepTomato, 1.0)
            ->addPreparation($prepBechamel, 1.0);

        $dishes['seabass'] = (new Dish('Pan-Seared Sea Bass & Ratatouille', 'main', 22.00))
            ->addIngredient($ing['Sea Bass Fillet'], 190.0, 'g')
            ->addIngredient($ing['Eggplant'], 100.0, 'g')
            ->addIngredient($ing['Bell Peppers'], 100.0, 'g')
            ->addPreparation($prepTomato, 0.5);

        // Desserts (4)
        $dishes['tiramisu'] = (new Dish('Homemade Classic Tiramisu', 'dessert', 8.00))
            ->addIngredient($ing['Mascarpone'], 90.0, 'g')
            ->addIngredient($ing['Ladyfinger Biscuits'], 50.0, 'g')
            ->addIngredient($ing['Free-Range Eggs'], 1.0, 'piece')
            ->addIngredient($ing['Caster Sugar'], 30.0, 'g');

        $dishes['fondant'] = (new Dish('Warm Dark Chocolate Fondant', 'dessert', 8.50))
            ->addIngredient($ing['Dark Chocolate 70%'], 80.0, 'g')
            ->addIngredient($ing['Unsalted Butter'], 50.0, 'g')
            ->addIngredient($ing['Free-Range Eggs'], 2.0, 'piece')
            ->addIngredient($ing['Caster Sugar'], 40.0, 'g');

        $dishes['millefeuille'] = (new Dish('Vanilla Cream & Hazelnut Tartlet', 'dessert', 7.50))
            ->addPreparation($prepPastryCream, 1.0)
            ->addIngredient($ing['Hazelnut Spread (Nutella)'], 35.0, 'g');

        $dishes['yogurt_honey'] = (new Dish('Greek Yogurt & Fresh Mint Bowl', 'dessert', 6.50))
            ->addIngredient($ing['Greek Yogurt'], 150.0, 'g')
            ->addIngredient($ing['Caster Sugar'], 15.0, 'g')
            ->addIngredient($ing['Fresh Mint'], 5.0, 'g');

        // Drinks (3)
        $dishes['lemonade'] = (new Dish('Fresh Mint Lemonade', 'drink', 4.50))
            ->addIngredient($ing['Lemons'], 150.0, 'g')
            ->addIngredient($ing['Fresh Mint'], 10.0, 'g')
            ->addIngredient($ing['Caster Sugar'], 25.0, 'g');

        $dishes['water'] = (new Dish('Mineral Water 500mL', 'drink', 3.00))
            ->addIngredient($ing['Mineral Water 500mL'], 1.0, 'piece');

        $dishes['milkshake'] = (new Dish('Artisan Chocolate Milkshake', 'drink', 6.00))
            ->addIngredient($ing['Whole Milk'], 250.0, 'mL')
            ->addIngredient($ing['Dark Chocolate 70%'], 40.0, 'g')
            ->addIngredient($ing['Heavy Cream'], 40.0, 'mL');

        foreach ($dishes as $dish) {
            $manager->persist($dish);
        }

        // 6. Composed Menus (3 menus)
        $menuLunch = (new Menu('Bistro Lunch Express', 24.00, 'Starter + Main + Drink'))
            ->addDish($dishes['bruschetta'])
            ->addDish($dishes['bolognese'])
            ->addDish($dishes['water']);
        $manager->persist($menuLunch);

        $menuSeasonal = (new Menu('Seasonal 3-Course Menu', 32.00, 'Starter + Main + Dessert of the Season'))
            ->addDish($dishes['caesar'])
            ->addDish($dishes['burger'])
            ->addDish($dishes['tiramisu']);
        $manager->persist($menuSeasonal);

        $menuChef = (new Menu('Chef Signature Tasting Menu', 46.00, 'Garlic Prawns + Beef Tenderloin + Chocolate Fondant'))
            ->addDish($dishes['garlic_prawns'])
            ->addDish($dishes['tenderloin'])
            ->addDish($dishes['fondant']);
        $manager->persist($menuChef);

        // 7. Sample Customer Orders (for immediate dashboard & kitchen display)
        $order1 = new CustomerOrder(4, 'Sofia Alaoui (Waiter)');
        $item1 = $order1->addItem($dishes['margherita'], 2);
        $item1->setStatus('served')->setUnitCostSnapshot(2.45);
        $item2 = $order1->addItem($dishes['burger'], 3);
        $item2->setStatus('served')->setUnitCostSnapshot(3.60);
        $item3 = $order1->addItem($dishes['tiramisu'], 2);
        $item3->setStatus('served')->setUnitCostSnapshot(1.62);
        $order1->setStatus('closed');
        $manager->persist($order1);

        $order2 = new CustomerOrder(7, 'Sofia Alaoui (Waiter)');
        $order2->addItem($dishes['caesar'], 2)->setStatus('preparing');
        $order2->addItem($dishes['tenderloin'], 2)->setStatus('pending');
        $order2->addItem($dishes['fondant'], 1)->setStatus('pending');
        $manager->persist($order2);

        $manager->flush();
    }
}
