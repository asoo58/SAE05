<?php

namespace App\Controller\Api;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Calendrier;
use App\Entity\Depot;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/abonnements')]
#[OA\Tag(name: 'Abonnements', description: 'Gestion des abonnements aux paniers Cocagne')]
class AbonnementController extends AbstractController
{
    #[Route('', name: 'api_abonnements_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Liste des abonnements')]
    #[OA\Parameter(
        name: 'adherent_id',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant d\'adhérent',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste complète des abonnements.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Abonnement::class, groups: ['api_read'])))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $criteres = [];
        if ($request->query->has('adherent_id') && $request->query->get('adherent_id') !== '') {
            $criteres['adherent'] = (int) $request->query->get('adherent_id');
        }

        $abonnements = !empty($criteres)
            ? $em->getRepository(Abonnement::class)->findBy($criteres, ['id' => 'ASC'])
            : $em->getRepository(Abonnement::class)->findAll();

        return $this->json($abonnements, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_abonnements_get_one', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Détail d\'un abonnement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'abonnement', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Détail de l\'abonnement retourné.',
        content: new OA\JsonContent(ref: new Model(type: Abonnement::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'Abonnement introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $abonnement = $em->getRepository(Abonnement::class)->find($id);

        if (!$abonnement) {
            return $this->json(['error' => 'Abonnement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($abonnement, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_abonnements_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Créer un abonnement')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un abonnement',
        required: true,
        content: new OA\JsonContent(
            required: ['adherent_id', 'produit_id', 'calendrier_id'],
            properties: [
                new OA\Property(property: 'adherent_id', type: 'integer', example: 1),
                new OA\Property(property: 'produit_id', type: 'integer', example: 1),
                new OA\Property(property: 'calendrier_id', type: 'integer', example: 1),
                new OA\Property(property: 'id_depot', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-11-01'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2027-11-01'),
                new OA\Property(property: 'remisePourcentage', type: 'string', example: '5.50'),
                new OA\Property(property: 'statut', type: 'string', example: 'actif')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Abonnement créé avec succès.', content: new OA\JsonContent(ref: new Model(type: Abonnement::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides ou entité liée introuvable.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adherent_id'], $data['produit_id'], $data['calendrier_id'])) {
            return $this->json(['error' => 'Les IDs de l\'adhérent, du produit et du calendrier sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = $em->getRepository(Adherent::class)->find((int) $data['adherent_id']);
        $produit = $em->getRepository(Produit::class)->find((int) $data['produit_id']);
        $calendrier = $em->getRepository(Calendrier::class)->find((int) $data['calendrier_id']);

        if (!$adherent || !$produit || !$calendrier) {
            return $this->json(['error' => 'L\'une des entités liées (Adhérent, Produit, Calendrier) est introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $abonnement = new Abonnement();
        $abonnement->setAdherent($adherent);
        $abonnement->setProduit($produit);
        $abonnement->setCalendrier($calendrier);

        if (!empty($data['date_debut'])) {
            try {
                $abonnement->setDateDebut(new \DateTime($data['date_debut']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_debut.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (!empty($data['date_fin'])) {
            try {
                $abonnement->setDateFin(new \DateTime($data['date_fin']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_fin.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['remisePourcentage'])) {
            $abonnement->setRemisePourcentage((string) $data['remisePourcentage']);
        }
        if (!empty($data['statut'])) {
            $abonnement->setStatut($data['statut']);
        }

        if (!empty($data['id_depot']) || !empty($data['depot_id'])) {
            $depotId = $data['id_depot'] ?? $data['depot_id'];
            $depot = $em->getRepository(Depot::class)->find((int) $depotId);
            if ($depot) {
                $abonnement->setDepot($depot);
            }
        }

        $em->persist($abonnement);
        $em->flush();

        return $this->json($abonnement, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_abonnements_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifier un abonnement')]
    #[OA\Patch(summary: 'Modifier partiellement un abonnement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'abonnement à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs de l\'abonnement à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'statut', type: 'string', example: 'suspendu'),
                new OA\Property(property: 'remisePourcentage', type: 'string', example: '10.00'),
                new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-11-01'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2027-11-01'),
                new OA\Property(property: 'adherent_id', type: 'integer', example: 1),
                new OA\Property(property: 'produit_id', type: 'integer', example: 1),
                new OA\Property(property: 'calendrier_id', type: 'integer', example: 1),
                new OA\Property(property: 'id_depot', type: 'integer', nullable: true, example: 1)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Abonnement mis à jour avec succès.', content: new OA\JsonContent(ref: new Model(type: Abonnement::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Abonnement introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $abonnement = $em->getRepository(Abonnement::class)->find($id);
        if (!$abonnement) {
            return $this->json(['error' => 'Abonnement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('statut', $data)) {
            $abonnement->setStatut($data['statut']);
        }
        if (array_key_exists('remisePourcentage', $data)) {
            $abonnement->setRemisePourcentage((string) $data['remisePourcentage']);
        }
        if (array_key_exists('date_debut', $data)) {
            if ($data['date_debut'] !== null) {
                try {
                    $abonnement->setDateDebut(new \DateTime($data['date_debut']));
                } catch (\Exception) {
                    return $this->json(['error' => 'Format de date invalide pour date_debut.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }
        if (array_key_exists('date_fin', $data)) {
            if ($data['date_fin'] !== null) {
                try {
                    $abonnement->setDateFin(new \DateTime($data['date_fin']));
                } catch (\Exception) {
                    return $this->json(['error' => 'Format de date invalide pour date_fin.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }
        if (!empty($data['adherent_id'])) {
            $adherent = $em->getRepository(Adherent::class)->find((int) $data['adherent_id']);
            if (!$adherent) {
                return $this->json(['error' => 'Adhérent introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $abonnement->setAdherent($adherent);
        }
        if (!empty($data['produit_id'])) {
            $produit = $em->getRepository(Produit::class)->find((int) $data['produit_id']);
            if (!$produit) {
                return $this->json(['error' => 'Produit introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $abonnement->setProduit($produit);
        }
        if (!empty($data['calendrier_id'])) {
            $calendrier = $em->getRepository(Calendrier::class)->find((int) $data['calendrier_id']);
            if (!$calendrier) {
                return $this->json(['error' => 'Calendrier introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $abonnement->setCalendrier($calendrier);
        }
        if (array_key_exists('id_depot', $data) || array_key_exists('depot_id', $data)) {
            $depotId = $data['id_depot'] ?? $data['depot_id'];
            if ($depotId === null) {
                $abonnement->setDepot(null);
            } else {
                $depot = $em->getRepository(Depot::class)->find((int) $depotId);
                if (!$depot) {
                    return $this->json(['error' => 'Dépôt introuvable.'], Response::HTTP_BAD_REQUEST);
                }
                $abonnement->setDepot($depot);
            }
        }

        $em->flush();
        return $this->json($abonnement, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_abonnements_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Supprimer un abonnement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'abonnement à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Abonnement supprimé avec succès.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Abonnement introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $abonnement = $em->getRepository(Abonnement::class)->find($id);
        if (!$abonnement) {
            return $this->json(['error' => 'Abonnement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($abonnement);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
