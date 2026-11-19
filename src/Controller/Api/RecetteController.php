<?php

namespace App\Controller\Api;

use App\Entity\Recette;
use App\Repository\RecetteRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recettes')]
#[OA\Tag(name: 'Recettes', description: 'Gestion des recettes culinaires et anti-gaspillage')]
final class RecetteController extends AbstractController
{
    #[Route('', name: 'api_recettes_index', methods: ['GET'])]
    #[OA\Parameter(
        name: 'partage_national',
        in: 'query',
        description: 'Filtrer par partage national (true/false)',
        required: false,
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(response: 200, description: 'Liste des recettes')]
    public function index(Request $request, RecetteRepository $repository): JsonResponse
    {
        $criteres = [];
        if ($request->query->has('partage_national') && $request->query->get('partage_national') !== '') {
            $criteres['partage_national'] = filter_var($request->query->get('partage_national'), FILTER_VALIDATE_BOOLEAN);
        }

        $recettes = empty($criteres)
            ? $repository->findBy([], ['titre' => 'ASC'])
            : $repository->findBy($criteres, ['titre' => 'ASC']);

        return $this->json($recettes, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_recettes_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Détail d\'une recette')]
    #[OA\Response(response: 404, description: 'Recette introuvable')]
    public function show(int $id, RecetteRepository $repository): JsonResponse
    {
        $recette = $repository->find($id);

        if (!$recette) {
            return $this->json(['error' => 'Recette introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($recette, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_recettes_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['titre', 'instructions'],
            properties: [
                new OA\Property(property: 'titre', type: 'string', maxLength: 200, example: 'Velouté de potimarron'),
                new OA\Property(property: 'instructions', type: 'string', example: '1. Couper le potimarron en dés. 2. Cuire 20 minutes...'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Un velouté crémeux idéal pour les soirées d\'automne'),
                new OA\Property(property: 'temps_preparation_min', type: 'integer', nullable: true, example: 15),
                new OA\Property(property: 'temps_cuisson_min', type: 'integer', nullable: true, example: 25),
                new OA\Property(property: 'nb_personnes', type: 'integer', example: 4),
                new OA\Property(property: 'partage_national', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure_origine', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Recette créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou obligatoires manquantes')]
    #[OA\Response(response: 404, description: 'Structure d\'origine introuvable')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['titre']) || empty($donnees['instructions'])) {
            return $this->json(
                ['error' => 'Les champs titre et instructions sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $recette = new Recette();
        $recette->setTitre(trim((string) $donnees['titre']));
        $recette->setInstructions(trim((string) $donnees['instructions']));

        if (isset($donnees['description'])) {
            $recette->setDescription($donnees['description']);
        }
        if (isset($donnees['temps_preparation_min'])) {
            $recette->setTempsPreparationMin((int) $donnees['temps_preparation_min']);
        }
        if (isset($donnees['temps_cuisson_min'])) {
            $recette->setTempsCuissonMin((int) $donnees['temps_cuisson_min']);
        }
        if (isset($donnees['nb_personnes'])) {
            $recette->setNbPersonnes((int) $donnees['nb_personnes']);
        }
        if (array_key_exists('partage_national', $donnees)) {
            $recette->setPartageNational((bool) $donnees['partage_national']);
        }

        $idStructure = $donnees['id_structure_origine'] ?? $donnees['structure_origine_id'] ?? null;
        if ($idStructure) {
            $structure = $structureRepository->find($idStructure);
            if (!$structure) {
                return $this->json(['error' => 'Structure d\'origine introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $recette->setStructureOrigine($structure);
        }

        $em->persist($recette);
        $em->flush();

        return $this->json($recette, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_recettes_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'titre', type: 'string', maxLength: 200, example: 'Velouté onctueux de potimarron'),
                new OA\Property(property: 'instructions', type: 'string', example: '1. Nettoyer et couper le potimarron...'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Version enrichie aux épices douces'),
                new OA\Property(property: 'temps_preparation_min', type: 'integer', nullable: true, example: 20),
                new OA\Property(property: 'temps_cuisson_min', type: 'integer', nullable: true, example: 30),
                new OA\Property(property: 'nb_personnes', type: 'integer', example: 6),
                new OA\Property(property: 'partage_national', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure_origine', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Recette mise à jour')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Recette ou structure introuvable')]
    public function update(
        int $id,
        Request $request,
        RecetteRepository $repository,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $recette = $repository->find($id);

        if (!$recette) {
            return $this->json(['error' => 'Recette introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('titre', $donnees)) {
            $titre = trim((string) $donnees['titre']);
            if ($titre === '') {
                return $this->json(['error' => 'Le champ titre ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $recette->setTitre($titre);
        }

        if (array_key_exists('instructions', $donnees)) {
            $instructions = trim((string) $donnees['instructions']);
            if ($instructions === '') {
                return $this->json(['error' => 'Le champ instructions ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $recette->setInstructions($instructions);
        }

        if (array_key_exists('description', $donnees)) {
            $recette->setDescription($donnees['description']);
        }
        if (array_key_exists('temps_preparation_min', $donnees)) {
            $recette->setTempsPreparationMin($donnees['temps_preparation_min'] !== null ? (int) $donnees['temps_preparation_min'] : null);
        }
        if (array_key_exists('temps_cuisson_min', $donnees)) {
            $recette->setTempsCuissonMin($donnees['temps_cuisson_min'] !== null ? (int) $donnees['temps_cuisson_min'] : null);
        }
        if (array_key_exists('nb_personnes', $donnees)) {
            $recette->setNbPersonnes((int) $donnees['nb_personnes']);
        }
        if (array_key_exists('partage_national', $donnees)) {
            $recette->setPartageNational((bool) $donnees['partage_national']);
        }

        if (array_key_exists('id_structure_origine', $donnees) || array_key_exists('structure_origine_id', $donnees)) {
            $idStructure = $donnees['id_structure_origine'] ?? $donnees['structure_origine_id'];
            if ($idStructure === null) {
                $recette->setStructureOrigine(null);
            } else {
                $structure = $structureRepository->find($idStructure);
                if (!$structure) {
                    return $this->json(['error' => 'Structure d\'origine introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $recette->setStructureOrigine($structure);
            }
        }

        $em->flush();

        return $this->json($recette, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_recettes_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Recette supprimée')]
    #[OA\Response(response: 404, description: 'Recette introuvable')]
    public function delete(
        int $id,
        RecetteRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $recette = $repository->find($id);

        if (!$recette) {
            return $this->json(['error' => 'Recette introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($recette);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
