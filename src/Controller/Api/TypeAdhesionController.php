<?php

namespace App\Controller\Api;

use App\Entity\TypeAdhesion;
use App\Repository\StructureRepository;
use App\Repository\TypeAdhesionRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/type-adhesions')]
#[OA\Tag(name: 'Types d\'adhésion', description: 'Gestion des types d\'adhésion')]
final class TypeAdhesionController extends AbstractController
{
    #[Route('', name: 'api_type_adhesions_index', methods: ['GET'])]
    #[OA\Get(summary: "Liste des types d'adhésion")]
    #[OA\Parameter(
        name: 'id_structure',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de structure',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Liste des types d\'adhésion')]
    public function index(Request $request, TypeAdhesionRepository $repository): JsonResponse
    {
        $criteres = [];

        if ($request->query->has('id_structure') && $request->query->get('id_structure') !== '') {
            $criteres['structure'] = (int) $request->query->get('id_structure');
        }

        $typesAdhesion = $repository->findBy($criteres, ['libelle' => 'ASC', 'id' => 'ASC']);

        return $this->json($typesAdhesion, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_type_adhesions_show', methods: ['GET'])]
    #[OA\Get(summary: "Détail d'un type d'adhésion")]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du type d\'adhésion',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Détail d\'un type d\'adhésion')]
    #[OA\Response(response: 404, description: 'Type d\'adhésion introuvable')]
    public function show(int $id, TypeAdhesionRepository $repository): JsonResponse
    {
        $typeAdhesion = $repository->find($id);

        if (!$typeAdhesion) {
            return $this->json(['error' => 'Type d\'adhésion introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($typeAdhesion, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_type_adhesions_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un type d\'adhésion')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['libelle', 'montant', 'id_structure'],
            properties: [
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, example: 'Adhésion Annuelle Standard'),
                new OA\Property(property: 'montant', type: 'string', example: '25.00'),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Type d\'adhésion créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['libelle']) || !isset($donnees['montant']) || empty($donnees['id_structure'])) {
            return $this->json(
                ['error' => 'Les champs libelle, montant et id_structure sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_numeric($donnees['montant']) || (float) $donnees['montant'] < 0) {
            return $this->json(
                ['error' => 'Le montant doit être un nombre positif ou nul.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $structure = $structureRepository->find((int) $donnees['id_structure']);
        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $typeAdhesion = new TypeAdhesion();
        $typeAdhesion->setLibelle($donnees['libelle']);
        $typeAdhesion->setMontant((string) $donnees['montant']);
        $typeAdhesion->setStructure($structure);

        $em->persist($typeAdhesion);
        $em->flush();

        return $this->json($typeAdhesion, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_type_adhesions_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un type d\'adhésion')]
    #[OA\Patch(summary: 'Modifier partiellement un type d\'adhésion')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du type d\'adhésion',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, example: 'Adhésion Annuelle Solidaire'),
                new OA\Property(property: 'montant', type: 'string', example: '15.00'),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Type d\'adhésion mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Type d\'adhésion introuvable')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TypeAdhesionRepository $typeAdhesionRepository,
        StructureRepository $structureRepository
    ): JsonResponse {
        $typeAdhesion = $typeAdhesionRepository->find($id);

        if (!$typeAdhesion) {
            return $this->json(['error' => 'Type d\'adhésion introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('libelle', $donnees)) {
            if (empty($donnees['libelle'])) {
                return $this->json(['error' => 'Le libellé ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $typeAdhesion->setLibelle($donnees['libelle']);
        }

        if (array_key_exists('montant', $donnees)) {
            if (!is_numeric($donnees['montant']) || (float) $donnees['montant'] < 0) {
                return $this->json(['error' => 'Le montant doit être un nombre positif ou nul.'], Response::HTTP_BAD_REQUEST);
            }
            $typeAdhesion->setMontant((string) $donnees['montant']);
        }

        if (array_key_exists('id_structure', $donnees)) {
            $structure = $structureRepository->find((int) $donnees['id_structure']);
            if (!$structure) {
                return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $typeAdhesion->setStructure($structure);
        }

        $em->flush();

        return $this->json($typeAdhesion, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_type_adhesions_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un type d\'adhésion')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du type d\'adhésion',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 204, description: 'Type d\'adhésion supprimé')]
    #[OA\Response(response: 404, description: 'Type d\'adhésion introuvable')]
    public function delete(
        int $id,
        TypeAdhesionRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $typeAdhesion = $repository->find($id);

        if (!$typeAdhesion) {
            return $this->json(['error' => 'Type d\'adhésion introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($typeAdhesion);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
