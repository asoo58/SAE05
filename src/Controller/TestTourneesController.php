<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ancienne page de test : conservée comme alias de l'outil d'organisation.
 */
final class TestTourneesController extends AbstractController
{
    #[Route('/test/tournees', name: 'app_test_tournees', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_tournees');
    }
}
