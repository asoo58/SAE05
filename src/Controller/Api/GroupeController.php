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
    #[OA\Response(response: 200, description: 'Liste des groupes')]
    public function index(GroupeRepository $repository): JsonResponse
    {
        $groupes = $repository->findAll();

        return $this->json($groupes, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_groupes_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Détail d\'un groupe')]
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
    #[OA\RequestBody(
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
    #[OA\Response(response: 400, description: 'Données invalides ou manquantes')]
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

        $codeGroupe = trim((string) $donnees['code_groupe']);

        $groupeExistant = $repository->findOneBy(['code_groupe' => $codeGroupe]);
        if ($groupeExistant) {
            return $this->json(
                ['error' => 'Un groupe avec ce code_groupe existe déjà.'],
                Response::HTTP_CONFLICT
            );
        }

        $groupe = new Groupe();
        $groupe->setCodeGroupe($codeGroupe);
        $groupe->setLibelle($donnees['libelle'] ?? null);
        $groupe->setDescription($donnees['description'] ?? null);

        $em->persist($groupe);
        $em->flush();

        return $this->json($groupe, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_groupes_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code_groupe', type: 'string', maxLength: 50, example: 'MARAICHER_PRO'),
                new OA\Property(property: 'libelle', type: 'string', maxLength: 100, nullable: true, example: 'Équipe maraîchage avancée'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Membres expérimentés'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Groupe mis à jour')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Groupe introuvable')]
    #[OA\Response(response: 409, description: 'Ce code de groupe est déjà utilisé')]
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
            $nouveauCode = trim((string) $donnees['code_groupe']);
            if ($nouveauCode === '') {
                return $this->json(
                    ['error' => 'Le champ code_groupe ne peut pas être vide.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($nouveauCode !== $groupe->getCodeGroupe()) {
                $doublon = $repository->findOneBy(['code_groupe' => $nouveauCode]);
                if ($doublon) {
                    return $this->json(
                        ['error' => 'Ce code_groupe est déjà utilisé par un autre groupe.'],
                        Response::HTTP_CONFLICT
                    );
                }
                $groupe->setCodeGroupe($nouveauCode);
            }
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
    #[OA\Response(response: 204, description: 'Groupe supprimé')]
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
