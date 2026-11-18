<?php

namespace App\Controller\Api;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Authentification', description: 'Génération du token JWT pour déverrouiller l\'API')]
class AuthController extends AbstractController
{
    #[Route('/api/login_check', name: 'swagger_login_check', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Identifiants de connexion',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'prof@cocagne.fr', description: 'Adresse email (obligatoirement nommée username pour le pare-feu)'),
                new OA\Property(property: 'password', type: 'string', example: 'test')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Connexion réussie. Copiez le token généré et collez-le dans le bouton Authorize (cadenas).',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...')
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Identifiants invalides.')]
    public function login(): JsonResponse
    {
        return $this->json(['message' => 'Géré par LexikJWT']);
    }
}