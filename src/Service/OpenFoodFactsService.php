<?php

namespace App\Service;

use App\Entity\Ingredient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenFoodFactsService
{
    private const API_URL = 'https://world.openfoodfacts.org/api/v2/product/%s.json';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?EntityManagerInterface $em = null
    ) {}

    /**
     * Fetches product & allergen data from Open Food Facts by barcode and updates the Ingredient.
     * Preserves existing manual allergens if API returns missing/incomplete data.
     *
     * @return array{found: bool, productName: ?string, allergens: string[], message: string}
     */
    public function syncIngredientByBarcode(Ingredient $ingredient, ?string $overrideBarcode = null): array
    {
        $barcode = trim($overrideBarcode ?? (string) $ingredient->getBarcode());
        if ($barcode === '') {
            return [
                'found' => false,
                'productName' => null,
                'allergens' => $ingredient->getAllergens(),
                'message' => 'No barcode provided for Open Food Facts lookup.',
            ];
        }

        $ingredient->setBarcode($barcode);

        try {
            $response = $this->httpClient->request('GET', sprintf(self::API_URL, urlencode($barcode)), [
                'timeout' => 5.0,
                'headers' => ['User-Agent' => 'LeBistro60-PFE-Challenge/1.0'],
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'found' => false,
                    'productName' => null,
                    'allergens' => $ingredient->getAllergens(),
                    'message' => sprintf('Open Food Facts returned HTTP %d; keeping existing allergen declarations.', $response->getStatusCode()),
                ];
            }

            $payload = $response->toArray(false);
            if (($payload['status'] ?? 0) !== 1 || !isset($payload['product']) || !is_array($payload['product'])) {
                return [
                    'found' => false,
                    'productName' => null,
                    'allergens' => $ingredient->getAllergens(),
                    'message' => sprintf('Barcode %s not found on Open Food Facts; keeping existing allergen declarations.', $barcode),
                ];
            }

            $product = $payload['product'];
            $productName = $product['product_name'] ?? $ingredient->getName();
            $rawTags = is_array($product['allergens_tags'] ?? null) ? $product['allergens_tags'] : [];

            $normalized = [];
            foreach ($rawTags as $tag) {
                if (!is_string($tag) || $tag === '') {
                    continue;
                }
                $clean = strtolower(preg_replace('/^[a-z]{2}:/i', '', trim($tag)));
                if ($clean !== '') {
                    $normalized[] = $clean;
                }
            }

            // Merge with existing manual allergens so incomplete OFF records never erase chef-verified allergens
            $merged = array_values(array_unique(array_merge($ingredient->getAllergens(), $normalized)));
            $ingredient->setAllergens($merged);
            $this->em?->flush();

            return [
                'found' => true,
                'productName' => $productName,
                'allergens' => $merged,
                'message' => sprintf(
                    'Synced with Open Food Facts ("%s"): allergens [%s].',
                    $productName,
                    empty($merged) ? 'none declared' : implode(', ', $merged)
                ),
            ];
        } catch (\Throwable $e) {
            return [
                'found' => false,
                'productName' => null,
                'allergens' => $ingredient->getAllergens(),
                'message' => 'Open Food Facts API unreachable; retained local cached allergens.',
            ];
        }
    }
}
