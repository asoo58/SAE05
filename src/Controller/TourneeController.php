<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TourneeController extends AbstractController
{
    #[Route('/tournee', name: 'app_tournee')]
    public function index(): Response
    {
        return $this->render('tournee/index.html.twig', [
            'controller_name' => 'TourneeController',
        ]);
    }
}
