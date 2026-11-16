<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Service\OpenFoodFactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OpenFoodFactsController extends AbstractController
{
    #[Route('/backoffice/ingredient/{id}/sync-off', name: 'app_off_sync', methods: ['POST'])]
    public function sync(Ingredient $ingredient, Request $request, OpenFoodFactsService $offService): Response
    {
        $barcode = (string) $request->request->get('barcode', $ingredient->getBarcode());
        $result = $offService->syncIngredientByBarcode($ingredient, $barcode);

        if ($result['found']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_backoffice');
    }
}
