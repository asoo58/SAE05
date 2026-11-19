<?php

namespace App\Controller\Api;

use App\Entity\LivraisonTournee;
use App\Repository\ChauffeurRepository;
use App\Repository\LivraisonTourneeRepository;
use App\Repository\TourneeRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/livraison-tournees')]
#[OA\Tag(name: 'Livraisons de tournée', description: 'Gestion des livraisons associées aux tournées')]
final class LivraisonTourneeController extends AbstractController
{
    #[Route('', name: 'api_livraison_tournees_index', methods: ['GET'])]
    #[OA\Parameter(name: 'statut', in: 'query', description: 'Filtrer par statut', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id_tournee', in: 'query', description: 'Filtrer par tournée', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'id_chauffeur', in: 'query', description: 'Filtrer par chauffeur', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des livraisons de tournée')]
    public function index(Request $request, LivraisonTourneeRepository $repository): JsonResponse
    {
        $criteres = [];

        if ($request->query->has('statut') && $request->query->get('statut') !== '') {
            $criteres['statut'] = $request->query->get('statut');
        }

        $idTournee = $request->query->get('id_tournee') ?? $request->query->get('tournee_id');
        if ($idTournee) {
            $criteres['tournee'] = (int) $idTournee;
        }

        $idChauffeur = $request->query->get('id_chauffeur') ?? $request->query->get('chauffeur_id');
        if ($idChauffeur) {
            $criteres['chauffeur'] = (int) $idChauffeur;
        }

        $livraisons = empty($criteres)
            ? $repository->findBy([], ['date_livraison' => 'DESC'])
            : $repository->findBy($criteres, ['date_livraison' => 'DESC']);

        return $this->json($livraisons, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_livraison_tournees_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Détail d\'une livraison de tournée')]
    #[OA\Response(response: 404, description: 'Livraison introuvable')]
    public function show(int $id, LivraisonTourneeRepository $repository): JsonResponse
    {
        $livraison = $repository->find($id);

        if (!$livraison) {
            return $this->json(['error' => 'Livraison de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($livraison, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_livraison_tournees_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['date_livraison', 'id_chauffeur'],
            properties: [
                new OA\Property(property: 'date_livraison', type: 'string', format: 'date', example: '2026-11-20'),
                new OA\Property(property: 'id_chauffeur', type: 'integer', example: 1),
                new OA\Property(property: 'id_tournee', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_vehicule', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'statut', type: 'string', example: 'planifiee'),
                new OA\Property(property: 'itineraire_geojson', type: 'string', nullable: true, example: null),
                new OA\Property(property: 'distance_km', type: 'string', example: '42.50'),
                new OA\Property(property: 'duree_minutes', type: 'integer', example: 75),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Livraison de tournée créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou date manquante')]
    #[OA\Response(response: 404, description: 'Chauffeur, tournée ou véhicule introuvable')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        TourneeRepository $tourneeRepository,
        VehiculeRepository $vehiculeRepository,
        ChauffeurRepository $chauffeurRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['date_livraison'])) {
            return $this->json(
                ['error' => 'Le champ date_livraison est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $idChauffeur = $donnees['id_chauffeur'] ?? $donnees['chauffeur_id'] ?? null;
        if (!$idChauffeur) {
            return $this->json(
                ['error' => 'Le champ id_chauffeur est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $chauffeur = $chauffeurRepository->find($idChauffeur);
        if (!$chauffeur) {
            return $this->json(['error' => 'Chauffeur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $dateLivraison = new \DateTime($donnees['date_livraison']);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Format de date invalide pour date_livraison (attendu : YYYY-MM-DD).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $livraison = new LivraisonTournee();
        $livraison->setDateLivraison($dateLivraison);
        $livraison->setChauffeur($chauffeur);

        if (isset($donnees['statut'])) {
            $livraison->setStatut($donnees['statut']);
        }
        if (isset($donnees['itineraire_geojson'])) {
            $livraison->setItineraireGeojson($donnees['itineraire_geojson']);
        }
        if (isset($donnees['distance_km'])) {
            $livraison->setDistanceKm($donnees['distance_km']);
        }
        if (isset($donnees['duree_minutes'])) {
            $livraison->setDureeMinutes((int) $donnees['duree_minutes']);
        }

        $idTournee = $donnees['id_tournee'] ?? $donnees['tournee_id'] ?? null;
        if ($idTournee) {
            $tournee = $tourneeRepository->find($idTournee);
            if (!$tournee) {
                return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $livraison->setTournee($tournee);
        }

        $idVehicule = $donnees['id_vehicule'] ?? $donnees['vehicule_id'] ?? null;
        if ($idVehicule) {
            $vehicule = $vehiculeRepository->find($idVehicule);
            if (!$vehicule) {
                return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $livraison->setVehicule($vehicule);
        }

        $em->persist($livraison);
        $em->flush();

        return $this->json($livraison, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_livraison_tournees_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_livraison', type: 'string', format: 'date', example: '2026-11-21'),
                new OA\Property(property: 'statut', type: 'string', example: 'en_cours'),
                new OA\Property(property: 'itineraire_geojson', type: 'string', nullable: true, example: null),
                new OA\Property(property: 'distance_km', type: 'string', example: '45.00'),
                new OA\Property(property: 'duree_minutes', type: 'integer', example: 80),
                new OA\Property(property: 'id_tournee', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_vehicule', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_chauffeur', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Livraison mise à jour')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Livraison, tournée, véhicule ou chauffeur introuvable')]
    public function update(
        int $id,
        Request $request,
        LivraisonTourneeRepository $repository,
        EntityManagerInterface $em,
        TourneeRepository $tourneeRepository,
        VehiculeRepository $vehiculeRepository,
        ChauffeurRepository $chauffeurRepository
    ): JsonResponse {
        $livraison = $repository->find($id);

        if (!$livraison) {
            return $this->json(['error' => 'Livraison de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('date_livraison', $donnees)) {
            try {
                $livraison->setDateLivraison(new \DateTime($donnees['date_livraison']));
            } catch (\Exception) {
                return $this->json(
                    ['error' => 'Format de date invalide pour date_livraison (attendu : YYYY-MM-DD).'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        if (array_key_exists('statut', $donnees)) {
            $livraison->setStatut($donnees['statut']);
        }
        if (array_key_exists('itineraire_geojson', $donnees)) {
            $livraison->setItineraireGeojson($donnees['itineraire_geojson']);
        }
        if (array_key_exists('distance_km', $donnees)) {
            $livraison->setDistanceKm($donnees['distance_km']);
        }
        if (array_key_exists('duree_minutes', $donnees)) {
            $livraison->setDureeMinutes($donnees['duree_minutes'] !== null ? (int) $donnees['duree_minutes'] : null);
        }

        if (array_key_exists('id_tournee', $donnees) || array_key_exists('tournee_id', $donnees)) {
            $idTournee = $donnees['id_tournee'] ?? $donnees['tournee_id'];
            if ($idTournee === null) {
                $livraison->setTournee(null);
            } else {
                $tournee = $tourneeRepository->find($idTournee);
                if (!$tournee) {
                    return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $livraison->setTournee($tournee);
            }
        }

        if (array_key_exists('id_vehicule', $donnees) || array_key_exists('vehicule_id', $donnees)) {
            $idVehicule = $donnees['id_vehicule'] ?? $donnees['vehicule_id'];
            if ($idVehicule === null) {
                $livraison->setVehicule(null);
            } else {
                $vehicule = $vehiculeRepository->find($idVehicule);
                if (!$vehicule) {
                    return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $livraison->setVehicule($vehicule);
            }
        }

        if (array_key_exists('id_chauffeur', $donnees) || array_key_exists('chauffeur_id', $donnees)) {
            $idChauffeur = $donnees['id_chauffeur'] ?? $donnees['chauffeur_id'];
            if ($idChauffeur !== null) {
                $chauffeur = $chauffeurRepository->find($idChauffeur);
                if (!$chauffeur) {
                    return $this->json(['error' => 'Chauffeur introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $livraison->setChauffeur($chauffeur);
            }
        }

        $em->flush();

        return $this->json($livraison, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_livraison_tournees_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Livraison supprimée')]
    #[OA\Response(response: 404, description: 'Livraison introuvable')]
    public function delete(
        int $id,
        LivraisonTourneeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $livraison = $repository->find($id);

        if (!$livraison) {
            return $this->json(['error' => 'Livraison de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($livraison);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
