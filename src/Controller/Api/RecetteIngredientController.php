<?php

namespace App\Controller\Api;

use App\Entity\RecetteIngredient;
use App\Repository\IngredientRepository;
use App\Repository\RecetteIngredientRepository;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recette-ingredients')]
#[OA\Tag(name: 'Ingrédients de recette', description: 'Gestion des ingrédients associés aux recettes')]
final class RecetteIngredientController extends AbstractController
{
    #[Route('', name: 'api_recette_ingredients_index', methods: ['GET'])]
    #[OA\Parameter(
        name: 'id_recette',
        in: 'query',
        description: 'Filtrer par identifiant de recette',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'id_ingredient',
        in: 'query',
        description: 'Filtrer par identifiant d\'ingrédient',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Liste des ingrédients associés aux recettes')]
    public function index(Request $request, RecetteIngredientRepository $repository): JsonResponse
    {
        $idRecette = $request->query->get('id_recette') ?? $request->query->get('recette_id');
        $idIngredient = $request->query->get('id_ingredient') ?? $request->query->get('ingredient_id');

        $criteres = [];
        if ($idRecette) {
            $criteres['recette'] = (int) $idRecette;
        }
        if ($idIngredient) {
            $criteres['ingredient'] = (int) $idIngredient;
        }

        $liste = $repository->findBy($criteres);

        return $this->json($liste, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{recetteId}/{ingredientId}', name: 'api_recette_ingredients_show', methods: ['GET'])]
    #[OA\Parameter(name: 'recetteId', in: 'path', description: 'Identifiant de la recette', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'ingredientId', in: 'path', description: 'Identifiant de l\'ingrédient', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Détail d\'un ingrédient de recette')]
    #[OA\Response(response: 404, description: 'Association introuvable')]
    public function show(int $recetteId, int $ingredientId, RecetteIngredientRepository $repository): JsonResponse
    {
        $association = $repository->findOneBy(['recette' => $recetteId, 'ingredient' => $ingredientId]);

        if (!$association) {
            return $this->json(['error' => 'Association recette-ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($association, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_recette_ingredients_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id_recette', 'id_ingredient'],
            properties: [
                new OA\Property(property: 'id_recette', type: 'integer', example: 1),
                new OA\Property(property: 'id_ingredient', type: 'integer', example: 3),
                new OA\Property(property: 'quantite', type: 'string', example: '500.00'),
                new OA\Property(property: 'unite', type: 'string', maxLength: 30, example: 'g'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Ingrédient ajouté à la recette')]
    #[OA\Response(response: 400, description: 'Données invalides ou identifiants manquants')]
    #[OA\Response(response: 404, description: 'Recette ou ingrédient introuvable')]
    #[OA\Response(response: 409, description: 'Cet ingrédient est déjà présent dans cette recette')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        RecetteRepository $recetteRepository,
        IngredientRepository $ingredientRepository,
        RecetteIngredientRepository $repository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        $idRecette = $donnees['id_recette'] ?? $donnees['recette_id'] ?? null;
        $idIngredient = $donnees['id_ingredient'] ?? $donnees['ingredient_id'] ?? null;

        if (!$idRecette || !$idIngredient) {
            return $this->json(
                ['error' => 'Les champs id_recette et id_ingredient sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $recette = $recetteRepository->find($idRecette);
        if (!$recette) {
            return $this->json(['error' => 'Recette introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $ingredient = $ingredientRepository->find($idIngredient);
        if (!$ingredient) {
            return $this->json(['error' => 'Ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $existant = $repository->findOneBy(['recette' => $recette, 'ingredient' => $ingredient]);
        if ($existant) {
            return $this->json(
                ['error' => 'Cet ingrédient est déjà associé à cette recette.'],
                Response::HTTP_CONFLICT
            );
        }

        $association = new RecetteIngredient();
        $association->setRecette($recette);
        $association->setIngredient($ingredient);

        if (isset($donnees['quantite'])) {
            $association->setQuantite((string) $donnees['quantite']);
        }
        if (isset($donnees['unite'])) {
            $association->setUnite($donnees['unite']);
        }

        $em->persist($association);
        $em->flush();

        return $this->json($association, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{recetteId}/{ingredientId}', name: 'api_recette_ingredients_update', methods: ['PUT', 'PATCH'])]
    #[OA\Parameter(name: 'recetteId', in: 'path', description: 'Identifiant de la recette', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'ingredientId', in: 'path', description: 'Identifiant de l\'ingrédient', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'quantite', type: 'string', example: '750.00'),
                new OA\Property(property: 'unite', type: 'string', maxLength: 30, example: 'g'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Association mise à jour')]
    #[OA\Response(response: 404, description: 'Association introuvable')]
    public function update(
        int $recetteId,
        int $ingredientId,
        Request $request,
        RecetteIngredientRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $association = $repository->findOneBy(['recette' => $recetteId, 'ingredient' => $ingredientId]);

        if (!$association) {
            return $this->json(['error' => 'Association recette-ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('quantite', $donnees)) {
            $association->setQuantite($donnees['quantite'] !== null ? (string) $donnees['quantite'] : null);
        }
        if (array_key_exists('unite', $donnees)) {
            $association->setUnite($donnees['unite']);
        }

        $em->flush();

        return $this->json($association, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{recetteId}/{ingredientId}', name: 'api_recette_ingredients_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'recetteId', in: 'path', description: 'Identifiant de la recette', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'ingredientId', in: 'path', description: 'Identifiant de l\'ingrédient', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Ingrédient retiré de la recette')]
    #[OA\Response(response: 404, description: 'Association introuvable')]
    public function delete(
        int $recetteId,
        int $ingredientId,
        RecetteIngredientRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $association = $repository->findOneBy(['recette' => $recetteId, 'ingredient' => $ingredientId]);

        if (!$association) {
            return $this->json(['error' => 'Association recette-ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($association);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
