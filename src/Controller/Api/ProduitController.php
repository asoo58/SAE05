<?php

namespace App\Controller\Api;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/produits')]
#[OA\Tag(name: 'Produits', description: 'Gestion des produits et paniers')]
final class ProduitController extends AbstractController
{
    #[Route('', name: 'api_produits_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des produits')]
    #[OA\Response(response: 200, description: 'Liste des produits retournée avec succès')]
    public function index(ProduitRepository $repository): JsonResponse
    {
        $produits = $repository->findBy([], ['nom' => 'ASC']);

        return $this->json($produits, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_produits_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'un produit')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du produit', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail du produit retourné avec succès')]
    #[OA\Response(response: 404, description: 'Produit introuvable')]
    public function show(int $id, ProduitRepository $repository): JsonResponse
    {
        $produit = $repository->find($id);

        if (!$produit) {
            return $this->json(['error' => 'Produit introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($produit, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_produits_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un produit')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un produit',
        required: true,
        content: new OA\JsonContent(
            required: ['nom', 'unite', 'prix'],
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 120, example: 'Panier Bio Hebdo'),
                new OA\Property(property: 'unite', type: 'string', maxLength: 30, example: 'panier'),
                new OA\Property(property: 'prix', type: 'string', example: '18.50'),
                new OA\Property(property: 'marge', type: 'string', nullable: true, example: '2.50'),
                new OA\Property(property: 'encombrement_cagettes', type: 'integer', example: 1),
                new OA\Property(property: 'photo_url', type: 'string', maxLength: 255, nullable: true, example: 'https://example.com/panier.jpg'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Panier de légumes bio de saison'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Produit créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou manquantes')]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['nom']) || empty($donnees['unite']) || !isset($donnees['prix'])) {
            return $this->json(
                ['error' => 'Les champs nom, unite et prix sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_numeric($donnees['prix']) || (float) $donnees['prix'] < 0) {
            return $this->json(
                ['error' => 'Le prix doit être un nombre positif ou nul.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $produit = new Produit();
        $produit->setNom($donnees['nom']);
        $produit->setUnite($donnees['unite']);
        $produit->setPrix((string) $donnees['prix']);

        if (isset($donnees['marge'])) {
            if (!is_numeric($donnees['marge']) || (float) $donnees['marge'] < 0) {
                return $this->json(
                    ['error' => 'La marge doit être un nombre positif ou nul.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $produit->setMarge((string) $donnees['marge']);
        }

        if (isset($donnees['encombrement_cagettes'])) {
            $produit->setEncombrementCagettes((int) $donnees['encombrement_cagettes']);
        }

        if (array_key_exists('photo_url', $donnees)) {
            $produit->setPhotoUrl($donnees['photo_url']);
        }

        if (array_key_exists('description', $donnees)) {
            $produit->setDescription($donnees['description']);
        }

        $em->persist($produit);
        $em->flush();

        return $this->json($produit, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_produits_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un produit')]
    #[OA\Patch(summary: 'Modifier partiellement un produit')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du produit à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du produit à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 120, example: 'Grand Panier Bio'),
                new OA\Property(property: 'unite', type: 'string', maxLength: 30, example: 'panier'),
                new OA\Property(property: 'prix', type: 'string', example: '24.00'),
                new OA\Property(property: 'marge', type: 'string', nullable: true, example: '3.00'),
                new OA\Property(property: 'encombrement_cagettes', type: 'integer', example: 2),
                new OA\Property(property: 'photo_url', type: 'string', maxLength: 255, nullable: true, example: 'https://example.com/grand-panier.jpg'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Grand panier familial de légumes bio'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Produit mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Produit introuvable')]
    public function update(
        int $id,
        Request $request,
        ProduitRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $produit = $repository->find($id);

        if (!$produit) {
            return $this->json(['error' => 'Produit introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nom', $donnees)) {
            if (empty($donnees['nom'])) {
                return $this->json(['error' => 'Le nom ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $produit->setNom($donnees['nom']);
        }

        if (array_key_exists('unite', $donnees)) {
            if (empty($donnees['unite'])) {
                return $this->json(['error' => 'L\'unité ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $produit->setUnite($donnees['unite']);
        }

        if (array_key_exists('prix', $donnees)) {
            if (!is_numeric($donnees['prix']) || (float) $donnees['prix'] < 0) {
                return $this->json(['error' => 'Le prix doit être un nombre positif ou nul.'], Response::HTTP_BAD_REQUEST);
            }
            $produit->setPrix((string) $donnees['prix']);
        }

        if (array_key_exists('marge', $donnees)) {
            if ($donnees['marge'] !== null && (!is_numeric($donnees['marge']) || (float) $donnees['marge'] < 0)) {
                return $this->json(['error' => 'La marge doit être un nombre positif ou nul.'], Response::HTTP_BAD_REQUEST);
            }
            $produit->setMarge($donnees['marge'] !== null ? (string) $donnees['marge'] : null);
        }

        if (array_key_exists('encombrement_cagettes', $donnees)) {
            $produit->setEncombrementCagettes((int) $donnees['encombrement_cagettes']);
        }

        if (array_key_exists('photo_url', $donnees)) {
            $produit->setPhotoUrl($donnees['photo_url']);
        }

        if (array_key_exists('description', $donnees)) {
            $produit->setDescription($donnees['description']);
        }

        $em->flush();

        return $this->json($produit, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_produits_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un produit')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du produit à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Produit supprimé avec succès')]
    #[OA\Response(response: 404, description: 'Produit introuvable')]
    public function delete(
        int $id,
        ProduitRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $produit = $repository->find($id);

        if (!$produit) {
            return $this->json(['error' => 'Produit introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($produit);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
