<?php

namespace App\Tests\Unit;

use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Service\OpenFoodFactsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OpenFoodFactsServiceTest extends TestCase
{
    public function testSyncExtractsAndNormalizesAllergensAndPropagatesToDish(): void
    {
        $mockJson = json_encode([
            'status' => 1,
            'product' => [
                'product_name' => 'Nutella Hazelnut Spread',
                'allergens_tags' => ['en:milk', 'fr:nuts', 'en:soybeans'],
            ],
        ]);

        $client = new MockHttpClient([new MockResponse($mockJson, ['http_code' => 200])]);
        $service = new OpenFoodFactsService($client);

        $spread = new Ingredient('Hazelnut Spread', 'kg', 2.0, 1.0, 8.50);
        $dish = (new Dish('Crepe Nutella', 'dessert', 7.00))
            ->addIngredient($spread, 40.0, 'g');

        $result = $service->syncIngredientByBarcode($spread, '3017620422003');

        $this->assertTrue($result['found']);
        $this->assertSame('3017620422003', $spread->getBarcode());
        $this->assertSame(['milk', 'nuts', 'soybeans'], $spread->getAllergens());
        $this->assertSame(['milk', 'nuts', 'soybeans'], $dish->getAllergens());
    }

    public function testMissingOrUnreachableApiPreservesExistingAllergens(): void
    {
        $mockJson = json_encode(['status' => 0]);
        $client = new MockHttpClient([new MockResponse($mockJson, ['http_code' => 404])]);
        $service = new OpenFoodFactsService($client);

        $cheese = new Ingredient('Artisan Cheese', 'kg', 2.0, 1.0, 14.00, null, '9999999999999', ['milk']);
        $result = $service->syncIngredientByBarcode($cheese);

        $this->assertFalse($result['found']);
        $this->assertSame(['milk'], $cheese->getAllergens());
    }
}
