<?php

namespace App\Controller\Api;

use App\Entity\UtilisateurGroupe;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurGroupeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/utilisateur-groupes')]
#[OA\Tag(name: 'Affectations utilisateur-groupe', description: 'Gestion des affectations d\'utilisateurs à des groupes')]
final class UtilisateurGroupeController extends AbstractController
{
    #[Route('', name: 'api_utilisateur_groupes_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des affectations utilisateur-groupe')]
    #[OA\Parameter(
        name: 'id_utilisateur',
        in: 'query',
        description: 'Filtrer par identifiant d\'utilisateur',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'id_groupe',
        in: 'query',
        description: 'Filtrer par identifiant de groupe',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Liste des affectations utilisateur-groupe')]
    public function index(Request $request, UtilisateurGroupeRepository $repository): JsonResponse
    {
        $idUtilisateur = $request->query->get('id_utilisateur') ?? $request->query->get('utilisateur_id');
        $idGroupe = $request->query->get('id_groupe') ?? $request->query->get('groupe_id');

        $criteres = [];
        if ($idUtilisateur) {
            $criteres['utilisateur'] = (int) $idUtilisateur;
        }
        if ($idGroupe) {
            $criteres['groupe'] = (int) $idGroupe;
        }

        $affectations = $repository->findBy($criteres);

        return $this->json($affectations, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{utilisateurId}/{groupeId}', name: 'api_utilisateur_groupes_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'une affectation utilisateur-groupe')]
    #[OA\Parameter(name: 'utilisateurId', in: 'path', description: 'Identifiant de l\'utilisateur', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'groupeId', in: 'path', description: 'Identifiant du groupe', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Détail de l\'affectation utilisateur-groupe')]
    #[OA\Response(response: 404, description: 'Affectation introuvable')]
    public function show(int $utilisateurId, int $groupeId, UtilisateurGroupeRepository $repository): JsonResponse
    {
        $affectation = $repository->findOneBy([
            'utilisateur' => $utilisateurId,
            'groupe' => $groupeId,
        ]);

        if (!$affectation) {
            return $this->json(['error' => 'Affectation utilisateur-groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($affectation, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_utilisateur_groupes_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer une affectation utilisateur-groupe')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id_utilisateur', 'id_groupe'],
            properties: [
                new OA\Property(property: 'id_utilisateur', type: 'integer', example: 1),
                new OA\Property(property: 'id_groupe', type: 'integer', example: 2),
                new OA\Property(property: 'date_affectation', type: 'string', format: 'date-time', nullable: true, example: '2026-11-19T10:00:00+01:00'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Affectation créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou identifiants manquants')]
    #[OA\Response(response: 404, description: 'Utilisateur ou groupe introuvable')]
    #[OA\Response(response: 409, description: 'Cet utilisateur est déjà affecté à ce groupe')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepository,
        GroupeRepository $groupeRepository,
        UtilisateurGroupeRepository $repository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        $idUtilisateur = $donnees['id_utilisateur'] ?? $donnees['utilisateur_id'] ?? null;
        $idGroupe = $donnees['id_groupe'] ?? $donnees['groupe_id'] ?? null;

        if (!$idUtilisateur || !$idGroupe) {
            return $this->json(
                ['error' => 'Les champs id_utilisateur et id_groupe sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $utilisateur = $utilisateurRepository->find((int) $idUtilisateur);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $groupe = $groupeRepository->find((int) $idGroupe);
        if (!$groupe) {
            return $this->json(['error' => 'Groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $existant = $repository->findOneBy([
            'utilisateur' => $utilisateur,
            'groupe' => $groupe,
        ]);

        if ($existant) {
            return $this->json(
                ['error' => 'Cet utilisateur est déjà affecté à ce groupe.'],
                Response::HTTP_CONFLICT
            );
        }

        $affectation = new UtilisateurGroupe();
        $affectation->setUtilisateur($utilisateur);
        $affectation->setGroupe($groupe);

        if (!empty($donnees['date_affectation'])) {
            try {
                $affectation->setDateAffectation(new \DateTime($donnees['date_affectation']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format de date invalide pour date_affectation.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $affectation->setDateAffectation(new \DateTime());
        }

        $em->persist($affectation);
        $em->flush();

        return $this->json($affectation, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{utilisateurId}/{groupeId}', name: 'api_utilisateur_groupes_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier une affectation utilisateur-groupe')]
    #[OA\Patch(summary: 'Modifier partiellement une affectation utilisateur-groupe')]
    #[OA\Parameter(name: 'utilisateurId', in: 'path', description: 'Identifiant de l\'utilisateur', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'groupeId', in: 'path', description: 'Identifiant du groupe', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_affectation', type: 'string', format: 'date-time', nullable: true, example: '2026-11-20T12:00:00+01:00'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Affectation mise à jour')]
    #[OA\Response(response: 400, description: 'Format de date invalide')]
    #[OA\Response(response: 404, description: 'Affectation introuvable')]
    public function update(
        int $utilisateurId,
        int $groupeId,
        Request $request,
        UtilisateurGroupeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $affectation = $repository->findOneBy([
            'utilisateur' => $utilisateurId,
            'groupe' => $groupeId,
        ]);

        if (!$affectation) {
            return $this->json(['error' => 'Affectation utilisateur-groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('date_affectation', $donnees)) {
            if ($donnees['date_affectation'] === null) {
                $affectation->setDateAffectation(null);
            } else {
                try {
                    $affectation->setDateAffectation(new \DateTime($donnees['date_affectation']));
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide pour date_affectation.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $em->flush();

        return $this->json($affectation, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{utilisateurId}/{groupeId}', name: 'api_utilisateur_groupes_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer une affectation utilisateur-groupe')]
    #[OA\Parameter(name: 'utilisateurId', in: 'path', description: 'Identifiant de l\'utilisateur', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'groupeId', in: 'path', description: 'Identifiant du groupe', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Affectation supprimée')]
    #[OA\Response(response: 404, description: 'Affectation introuvable')]
    public function delete(
        int $utilisateurId,
        int $groupeId,
        UtilisateurGroupeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $affectation = $repository->findOneBy([
            'utilisateur' => $utilisateurId,
            'groupe' => $groupeId,
        ]);

        if (!$affectation) {
            return $this->json(['error' => 'Affectation utilisateur-groupe introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($affectation);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
