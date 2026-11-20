<?php

namespace App\Controller\Api;

use App\Entity\Saison;
use App\Repository\SaisonRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/saisons')]
#[OA\Tag(name: 'Saisons', description: 'Gestion des saisons maraîchères')]
final class SaisonController extends AbstractController
{
    #[Route('', name: 'api_saisons_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des saisons')]
    #[OA\Parameter(name: 'id_structure', in: 'query', description: 'Filtrer par identifiant de structure', required: false, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Liste des saisons retournée avec succès')]
    public function index(Request $request, SaisonRepository $repository): JsonResponse
    {
        $idStructure = $request->query->get('id_structure') ?? $request->query->get('structure_id');

        $criteres = [];
        if ($idStructure) {
            $criteres['structure'] = (int) $idStructure;
        }

        $saisons = empty($criteres)
            ? $repository->findBy([], ['date_debut' => 'DESC'])
            : $repository->findBy($criteres, ['date_debut' => 'DESC']);

        return $this->json($saisons, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_saisons_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'une saison')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la saison', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail de la saison retourné avec succès')]
    #[OA\Response(response: 404, description: 'Saison introuvable')]
    public function show(int $id, SaisonRepository $repository): JsonResponse
    {
        $saison = $repository->find($id);

        if (!$saison) {
            return $this->json(['error' => 'Saison introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($saison, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_saisons_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer une saison')]
    #[OA\RequestBody(
        description: 'Données requises pour créer une saison',
        required: true,
        content: new OA\JsonContent(
            required: ['date_debut', 'date_fin', 'id_structure'],
            properties: [
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, nullable: true, example: 'Saison 2026-2027'),
                new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-04-01'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2027-03-31'),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Saison créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou dates manquantes')]
    #[OA\Response(response: 404, description: 'Structure introuvable')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['date_debut']) || empty($donnees['date_fin'])) {
            return $this->json(
                ['error' => 'Les champs date_debut et date_fin sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $idStructure = $donnees['id_structure'] ?? $donnees['structure_id'] ?? null;
        if (!$idStructure) {
            return $this->json(
                ['error' => 'Le champ id_structure est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $structure = $structureRepository->find($idStructure);
        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $dateDebut = new \DateTime($donnees['date_debut']);
            $dateFin = new \DateTime($donnees['date_fin']);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Format de date invalide (attendu : YYYY-MM-DD).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($dateFin < $dateDebut) {
            return $this->json(
                ['error' => 'La date de fin ne peut pas être antérieure à la date de début.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $saison = new Saison();
        $saison->setLibelle($donnees['libelle'] ?? null);
        $saison->setDateDebut($dateDebut);
        $saison->setDateFin($dateFin);
        $saison->setStructure($structure);

        $em->persist($saison);
        $em->flush();

        return $this->json($saison, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_saisons_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier une saison')]
    #[OA\Patch(summary: 'Modifier partiellement une saison')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la saison à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs de la saison à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, nullable: true, example: 'Saison 2026-2027 Pro'),
                new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-04-01'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2027-04-30'),
                new OA\Property(property: 'id_structure', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Saison mise à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Saison ou structure introuvable')]
    public function update(
        int $id,
        Request $request,
        SaisonRepository $repository,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $saison = $repository->find($id);

        if (!$saison) {
            return $this->json(['error' => 'Saison introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('libelle', $donnees)) {
            $saison->setLibelle($donnees['libelle']);
        }

        if (array_key_exists('date_debut', $donnees)) {
            try {
                $saison->setDateDebut(new \DateTime($donnees['date_debut']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date_debut invalide (attendu : YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (array_key_exists('date_fin', $donnees)) {
            try {
                $saison->setDateFin(new \DateTime($donnees['date_fin']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date_fin invalide (attendu : YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($saison->getDateFin() < $saison->getDateDebut()) {
            return $this->json(
                ['error' => 'La date de fin ne peut pas être antérieure à la date de début.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (array_key_exists('id_structure', $donnees) || array_key_exists('structure_id', $donnees)) {
            $idStructure = $donnees['id_structure'] ?? $donnees['structure_id'];
            if ($idStructure !== null) {
                $structure = $structureRepository->find($idStructure);
                if (!$structure) {
                    return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $saison->setStructure($structure);
            }
        }

        $em->flush();

        return $this->json($saison, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_saisons_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer une saison')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la saison à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Saison supprimée avec succès')]
    #[OA\Response(response: 404, description: 'Saison introuvable')]
    public function delete(
        int $id,
        SaisonRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $saison = $repository->find($id);

        if (!$saison) {
            return $this->json(['error' => 'Saison introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($saison);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
