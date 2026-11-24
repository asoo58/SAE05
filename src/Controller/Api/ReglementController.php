<?php

namespace App\Controller\Api;

use App\Entity\Reglement;
use App\Repository\AbonnementRepository;
use App\Repository\AdherentRepository;
use App\Repository\AdhesionRepository;
use App\Repository\ReglementRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reglements')]
#[OA\Tag(name: 'Règlements', description: 'Gestion des paiements et règlements des adhérents')]
final class ReglementController extends AbstractController
{
    #[Route('', name: 'api_reglements_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des règlements')]
    #[OA\Parameter(name: 'id_adherent', in: 'query', description: 'Filtrer par adhérent', required: false, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'statut', in: 'query', description: 'Filtrer par statut', required: false, schema: new OA\Schema(type: 'string', example: 'valide'))]
    #[OA\Parameter(name: 'mode_reglement', in: 'query', description: 'Filtrer par mode de règlement', required: false, schema: new OA\Schema(type: 'string', example: 'virement'))]
    #[OA\Response(response: 200, description: 'Liste des règlements retournée avec succès')]
    public function index(Request $request, ReglementRepository $repository): JsonResponse
    {
        $criteres = [];

        $idAdherent = $request->query->get('id_adherent') ?? $request->query->get('adherent_id');
        if ($idAdherent) {
            $criteres['adherent'] = (int) $idAdherent;
        }

        if ($request->query->has('statut') && $request->query->get('statut') !== '') {
            $criteres['statut'] = $request->query->get('statut');
        }

        if ($request->query->has('mode_reglement') && $request->query->get('mode_reglement') !== '') {
            $criteres['modeReglement'] = $request->query->get('mode_reglement');
        }

        $reglements = $repository->findBy($criteres, ['dateReglement' => 'DESC']);

        return $this->json($reglements, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_reglements_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'un règlement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du règlement', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail du règlement retourné avec succès')]
    #[OA\Response(response: 404, description: 'Règlement introuvable')]
    public function show(int $id, ReglementRepository $repository): JsonResponse
    {
        $reglement = $repository->find($id);

        if (!$reglement) {
            return $this->json(['error' => 'Règlement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($reglement, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_reglements_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un règlement')]
    #[OA\RequestBody(
        description: 'Données requises pour enregistrer un règlement',
        required: true,
        content: new OA\JsonContent(
            required: ['date_reglement', 'montant', 'mode_reglement', 'id_adherent'],
            properties: [
                new OA\Property(property: 'date_reglement', type: 'string', format: 'date', example: '2026-11-20'),
                new OA\Property(property: 'montant', type: 'string', example: '25.00'),
                new OA\Property(property: 'mode_reglement', type: 'string', maxLength: 30, example: 'virement'),
                new OA\Property(property: 'id_adherent', type: 'integer', example: 1),
                new OA\Property(property: 'statut', type: 'string', maxLength: 30, example: 'en_attente'),
                new OA\Property(property: 'id_abonnement', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_adhesion', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Règlement créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants')]
    #[OA\Response(response: 404, description: 'Adhérent, abonnement ou adhésion introuvable')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        AdherentRepository $adherentRepository,
        AbonnementRepository $abonnementRepository,
        AdhesionRepository $adhesionRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['date_reglement']) || !isset($donnees['montant']) || empty($donnees['mode_reglement'])) {
            return $this->json(
                ['error' => 'Les champs date_reglement, montant et mode_reglement sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $idAdherent = $donnees['id_adherent'] ?? $donnees['adherent_id'] ?? null;
        if (!$idAdherent) {
            return $this->json(
                ['error' => 'Le champ id_adherent est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $adherent = $adherentRepository->find($idAdherent);
        if (!$adherent) {
            return $this->json(['error' => 'Adhérent introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $dateReglement = new \DateTimeImmutable($donnees['date_reglement']);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Format de date invalide pour date_reglement (attendu : YYYY-MM-DD).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $reglement = new Reglement();
        $reglement->setDateReglement($dateReglement);
        $reglement->setMontant((string) $donnees['montant']);
        $reglement->setModeReglement(trim((string) $donnees['mode_reglement']));
        $reglement->setAdherent($adherent);

        if (isset($donnees['statut'])) {
            $reglement->setStatut(trim((string) $donnees['statut']));
        }

        $idAbonnement = $donnees['id_abonnement'] ?? $donnees['abonnement_id'] ?? null;
        if ($idAbonnement) {
            $abonnement = $abonnementRepository->find($idAbonnement);
            if (!$abonnement) {
                return $this->json(['error' => 'Abonnement introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $reglement->setAbonnement($abonnement);
        }

        $idAdhesion = $donnees['id_adhesion'] ?? $donnees['adhesion_id'] ?? null;
        if ($idAdhesion) {
            $adhesion = $adhesionRepository->find($idAdhesion);
            if (!$adhesion) {
                return $this->json(['error' => 'Adhésion introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $reglement->setAdhesion($adhesion);
        }

        $em->persist($reglement);
        $em->flush();

        return $this->json($reglement, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_reglements_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un règlement')]
    #[OA\Patch(summary: 'Modifier partiellement un règlement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du règlement à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du règlement à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_reglement', type: 'string', format: 'date', example: '2026-11-21'),
                new OA\Property(property: 'montant', type: 'string', example: '30.00'),
                new OA\Property(property: 'mode_reglement', type: 'string', maxLength: 30, example: 'carte_bancaire'),
                new OA\Property(property: 'statut', type: 'string', maxLength: 30, example: 'valide'),
                new OA\Property(property: 'id_abonnement', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_adhesion', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Règlement mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Règlement, abonnement ou adhésion introuvable')]
    public function update(
        int $id,
        Request $request,
        ReglementRepository $repository,
        EntityManagerInterface $em,
        AbonnementRepository $abonnementRepository,
        AdhesionRepository $adhesionRepository
    ): JsonResponse {
        $reglement = $repository->find($id);

        if (!$reglement) {
            return $this->json(['error' => 'Règlement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('date_reglement', $donnees)) {
            try {
                $reglement->setDateReglement(new \DateTimeImmutable($donnees['date_reglement']));
            } catch (\Exception) {
                return $this->json(
                    ['error' => 'Format de date invalide pour date_reglement (attendu : YYYY-MM-DD).'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        if (array_key_exists('montant', $donnees)) {
            $reglement->setMontant((string) $donnees['montant']);
        }

        if (array_key_exists('mode_reglement', $donnees)) {
            $reglement->setModeReglement(trim((string) $donnees['mode_reglement']));
        }

        if (array_key_exists('statut', $donnees)) {
            $reglement->setStatut(trim((string) $donnees['statut']));
        }

        if (array_key_exists('id_abonnement', $donnees) || array_key_exists('abonnement_id', $donnees)) {
            $idAbonnement = $donnees['id_abonnement'] ?? $donnees['abonnement_id'];
            if ($idAbonnement === null) {
                $reglement->setAbonnement(null);
            } else {
                $abonnement = $abonnementRepository->find($idAbonnement);
                if (!$abonnement) {
                    return $this->json(['error' => 'Abonnement introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $reglement->setAbonnement($abonnement);
            }
        }

        if (array_key_exists('id_adhesion', $donnees) || array_key_exists('adhesion_id', $donnees)) {
            $idAdhesion = $donnees['id_adhesion'] ?? $donnees['adhesion_id'];
            if ($idAdhesion === null) {
                $reglement->setAdhesion(null);
            } else {
                $adhesion = $adhesionRepository->find($idAdhesion);
                if (!$adhesion) {
                    return $this->json(['error' => 'Adhésion introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $reglement->setAdhesion($adhesion);
            }
        }

        $em->flush();

        return $this->json($reglement, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_reglements_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un règlement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du règlement à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Règlement supprimé avec succès')]
    #[OA\Response(response: 404, description: 'Règlement introuvable')]
    public function delete(
        int $id,
        ReglementRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $reglement = $repository->find($id);

        if (!$reglement) {
            return $this->json(['error' => 'Règlement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($reglement);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
