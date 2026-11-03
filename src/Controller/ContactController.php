<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $message = $request->request->get('message');

            if (!empty($nom) && !empty($email) && !empty($message)) {
                $this->addFlash('success', 'Merci ' . htmlspecialchars($nom) . ', votre message a bien été envoyé !');
                return $this->redirectToRoute('app_contact');
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs.');
        }

        return $this->render('contact/index.html.twig');
    }
}