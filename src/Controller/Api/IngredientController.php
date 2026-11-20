<?php

namespace App\Controller\Api;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ingredients')]
#[OA\Tag(name: 'Ingrédients', description: 'Gestion des ingrédients culinaires')]
final class IngredientController extends AbstractController
{
    #[Route('', name: 'api_ingredients_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des ingrédients')]
    #[OA\Parameter(
        name: 'rayon',
        in: 'query',
        description: 'Filtrer par rayon',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Légumes racines')
    )]
    #[OA\Response(response: 200, description: 'Liste des ingrédients retournée avec succès')]
    public function index(Request $request, IngredientRepository $repository): JsonResponse
    {
        $rayon = $request->query->get('rayon');

        $criteres = [];
        if ($rayon) {
            $criteres['rayon'] = $rayon;
        }

        $ingredients = empty($criteres)
            ? $repository->findBy([], ['nom' => 'ASC'])
            : $repository->findBy($criteres, ['nom' => 'ASC']);

        return $this->json($ingredients, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_ingredients_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'un ingrédient')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'ingrédient', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail de l\'ingrédient retourné avec succès')]
    #[OA\Response(response: 404, description: 'Ingrédient introuvable')]
    public function show(int $id, IngredientRepository $repository): JsonResponse
    {
        $ingredient = $repository->find($id);

        if (!$ingredient) {
            return $this->json(['error' => 'Ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($ingredient, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_ingredients_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un ingrédient')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un ingrédient',
        required: true,
        content: new OA\JsonContent(
            required: ['nom'],
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Carotte'),
                new OA\Property(property: 'rayon', type: 'string', maxLength: 50, nullable: true, example: 'Légumes racines'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Ingrédient créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou nom manquant')]
    #[OA\Response(response: 409, description: 'Un ingrédient avec ce nom existe déjà')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        IngredientRepository $repository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['nom'])) {
            return $this->json(
                ['error' => 'Le champ nom est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $existant = $repository->findOneBy(['nom' => trim($donnees['nom'])]);
        if ($existant) {
            return $this->json(
                ['error' => 'Un ingrédient avec ce nom existe déjà.'],
                Response::HTTP_CONFLICT
            );
        }

        $ingredient = new Ingredient();
        $ingredient->setNom(trim($donnees['nom']));

        if (array_key_exists('rayon', $donnees)) {
            $ingredient->setRayon($donnees['rayon']);
        }

        $em->persist($ingredient);
        $em->flush();

        return $this->json($ingredient, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_ingredients_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un ingrédient')]
    #[OA\Patch(summary: 'Modifier partiellement un ingrédient')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'ingrédient à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs de l\'ingrédient à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Carotte des sables'),
                new OA\Property(property: 'rayon', type: 'string', maxLength: 50, nullable: true, example: 'Légumes frais'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Ingrédient mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Ingrédient introuvable')]
    #[OA\Response(response: 409, description: 'Un ingrédient avec ce nom existe déjà')]
    public function update(
        int $id,
        Request $request,
        IngredientRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $ingredient = $repository->find($id);

        if (!$ingredient) {
            return $this->json(['error' => 'Ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nom', $donnees)) {
            $nouveauNom = trim($donnees['nom']);
            if (empty($nouveauNom)) {
                return $this->json(['error' => 'Le nom ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }

            if ($nouveauNom !== $ingredient->getNom()) {
                $existant = $repository->findOneBy(['nom' => $nouveauNom]);
                if ($existant && $existant->getId() !== $ingredient->getId()) {
                    return $this->json(
                        ['error' => 'Un ingrédient avec ce nom existe déjà.'],
                        Response::HTTP_CONFLICT
                    );
                }
            }

            $ingredient->setNom($nouveauNom);
        }

        if (array_key_exists('rayon', $donnees)) {
            $ingredient->setRayon($donnees['rayon']);
        }

        $em->flush();

        return $this->json($ingredient, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_ingredients_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un ingrédient')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'ingrédient à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Ingrédient supprimé avec succès')]
    #[OA\Response(response: 404, description: 'Ingrédient introuvable')]
    public function delete(
        int $id,
        IngredientRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $ingredient = $repository->find($id);

        if (!$ingredient) {
            return $this->json(['error' => 'Ingrédient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($ingredient);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
