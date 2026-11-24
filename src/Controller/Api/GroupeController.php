<?php

namespace App\Controller\Api;

use App\Entity\Groupe;
use App\Repository\GroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/groupes')]
#[OA\Tag(name: 'Groupes', description: 'Gestion des groupes d\'utilisateurs')]
final class GroupeController extends AbstractController
{
    #[Route('', name: 'api_groupes_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des groupes')]
    #[OA\Response(response: 200, description: 'Liste des groupes retournée avec succès')]
    public function index(GroupeRepository $repository): JsonResponse
    {
        $groupes = $repository->findAll();

        return $this->json($groupes, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_groupes_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'un groupe')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du groupe', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail du groupe retourné avec succès')]
    #[OA\Response(response: 404, description: 'Groupe introuvable')]
    public function show(int $id, GroupeRepository $repository): JsonResponse
    {
        $groupe = $repository->find($id);

        if (!$groupe) {
            return $this->json(['error' => 'Groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($groupe, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_groupes_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un groupe')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un groupe',
        required: true,
        content: new OA\JsonContent(
            required: ['code_groupe'],
            properties: [
                new OA\Property(property: 'code_groupe', type: 'string', maxLength: 50, example: 'MARAICHER'),
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, nullable: true, example: 'Équipe maraîchage'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Membres travaillant sur les parcelles de culture'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Groupe créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou code manquant')]
    #[OA\Response(response: 409, description: 'Un groupe avec ce code existe déjà')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        GroupeRepository $repository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['code_groupe'])) {
            return $this->json(
                ['error' => 'Le champ code_groupe est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $existant = $repository->findOneBy(['code_groupe' => $donnees['code_groupe']]);
        if ($existant) {
            return $this->json(
                ['error' => 'Un groupe avec ce code existe déjà.'],
                Response::HTTP_CONFLICT
            );
        }

        $groupe = new Groupe();
        $groupe->setCodeGroupe($donnees['code_groupe']);

        if (array_key_exists('libelle', $donnees)) {
            $groupe->setLibelle($donnees['libelle']);
        }
        if (array_key_exists('description', $donnees)) {
            $groupe->setDescription($donnees['description']);
        }

        $em->persist($groupe);
        $em->flush();

        return $this->json($groupe, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_groupes_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un groupe')]
    #[OA\Patch(summary: 'Modifier partiellement un groupe')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du groupe à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du groupe à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code_groupe', type: 'string', maxLength: 50, example: 'MARAICHER_RESP'),
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, nullable: true, example: 'Responsables maraîchage'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Encadrement des activités maraîchères'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Groupe mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Groupe introuvable')]
    #[OA\Response(response: 409, description: 'Un groupe avec ce code existe déjà')]
    public function update(
        int $id,
        Request $request,
        GroupeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $groupe = $repository->find($id);

        if (!$groupe) {
            return $this->json(['error' => 'Groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('code_groupe', $donnees)) {
            if (empty($donnees['code_groupe'])) {
                return $this->json(['error' => 'Le code_groupe ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }

            if ($donnees['code_groupe'] !== $groupe->getCodeGroupe()) {
                $existant = $repository->findOneBy(['code_groupe' => $donnees['code_groupe']]);
                if ($existant && $existant->getId() !== $groupe->getId()) {
                    return $this->json(
                        ['error' => 'Un groupe avec ce code existe déjà.'],
                        Response::HTTP_CONFLICT
                    );
                }
            }

            $groupe->setCodeGroupe($donnees['code_groupe']);
        }

        if (array_key_exists('libelle', $donnees)) {
            $groupe->setLibelle($donnees['libelle']);
        }
        if (array_key_exists('description', $donnees)) {
            $groupe->setDescription($donnees['description']);
        }

        $em->flush();

        return $this->json($groupe, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_groupes_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un groupe')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du groupe à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Groupe supprimé avec succès')]
    #[OA\Response(response: 404, description: 'Groupe introuvable')]
    public function delete(
        int $id,
        GroupeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $groupe = $repository->find($id);

        if (!$groupe) {
            return $this->json(['error' => 'Groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($groupe);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
