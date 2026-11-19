<?php

namespace App\Controller\Api;

use App\Entity\CalendrierJour;
use App\Repository\CalendrierJourRepository;
use App\Repository\CalendrierRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/calendrier-jours')]
#[OA\Tag(name: 'CalendrierJour', description: 'Gestion des jours du calendrier de livraison')]
final class CalendrierJourController extends AbstractController
{
    #[Route('', name: 'api_calendrier_jour_index', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des jours de calendrier')]
    public function index(CalendrierJourRepository $repository): JsonResponse
    {
        $jours = $repository->findAll();

        return $this->json($jours, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendrier_jour_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Détail d\'un jour de calendrier')]
    #[OA\Response(response: 404, description: 'Jour introuvable')]
    public function show(CalendrierJour $calendrierJour): JsonResponse
    {
        return $this->json($calendrierJour, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_calendrier_jour_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['date_livraison', 'date_preparation'],
            properties: [
                new OA\Property(property: 'date_livraison', type: 'string', format: 'date', example: '2026-11-24'),
                new OA\Property(property: 'date_preparation', type: 'string', format: 'date', example: '2026-11-23'),
                new OA\Property(property: 'est_livrable', type: 'boolean', example: true),
                new OA\Property(property: 'est_decale', type: 'boolean', example: false),
                new OA\Property(property: 'motif_decalage', type: 'string', maxLength: 255, nullable: true, example: null),
                new OA\Property(property: 'id_calendrier', type: 'integer', nullable: true, example: 8),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Jour de calendrier créé')]
    #[OA\Response(response: 400, description: 'Données invalides ou manquantes')]
    #[OA\Response(response: 404, description: 'Calendrier introuvable')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        CalendrierRepository $calendrierRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['date_livraison']) || empty($data['date_preparation'])) {
            return $this->json(
                ['error' => 'Les champs date_livraison et date_preparation sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $dateLivraison = new \DateTime($data['date_livraison']);
            $datePreparation = new \DateTime($data['date_preparation']);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Format de date invalide (attendu : YYYY-MM-DD).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $jour = new CalendrierJour();
        $jour->setDateLivraison($dateLivraison);
        $jour->setDatePreparation($datePreparation);
        $jour->setEstLivrable(isset($data['est_livrable']) ? (bool) $data['est_livrable'] : true);
        $jour->setEstDecale(isset($data['est_decale']) ? (bool) $data['est_decale'] : false);
        $jour->setMotifDecalage($data['motif_decalage'] ?? null);

        if (!empty($data['id_calendrier'])) {
            $calendrier = $calendrierRepository->find($data['id_calendrier']);
            if (!$calendrier) {
                return $this->json(['error' => 'Calendrier introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $jour->setCalendrier($calendrier);
        }

        $em->persist($jour);
        $em->flush();

        return $this->json($jour, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendrier_jour_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_livraison', type: 'string', format: 'date', example: '2026-11-25'),
                new OA\Property(property: 'date_preparation', type: 'string', format: 'date', example: '2026-11-24'),
                new OA\Property(property: 'est_livrable', type: 'boolean', example: true),
                new OA\Property(property: 'est_decale', type: 'boolean', example: true),
                new OA\Property(property: 'motif_decalage', type: 'string', maxLength: 255, nullable: true, example: 'Jour férié'),
                new OA\Property(property: 'id_calendrier', type: 'integer', nullable: true, example: 8),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Jour de calendrier mis à jour')]
    #[OA\Response(response: 400, description: 'Format de date invalide')]
    #[OA\Response(response: 404, description: 'Jour ou calendrier introuvable')]
    public function update(
        Request $request,
        CalendrierJour $calendrierJour,
        EntityManagerInterface $em,
        CalendrierRepository $calendrierRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            if (isset($data['date_livraison'])) {
                $calendrierJour->setDateLivraison(new \DateTime($data['date_livraison']));
            }
            if (isset($data['date_preparation'])) {
                $calendrierJour->setDatePreparation(new \DateTime($data['date_preparation']));
            }
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Format de date invalide (attendu : YYYY-MM-DD).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (array_key_exists('est_livrable', $data)) {
            $calendrierJour->setEstLivrable((bool) $data['est_livrable']);
        }
        if (array_key_exists('est_decale', $data)) {
            $calendrierJour->setEstDecale((bool) $data['est_decale']);
        }
        if (array_key_exists('motif_decalage', $data)) {
            $calendrierJour->setMotifDecalage($data['motif_decalage']);
        }
        if (array_key_exists('id_calendrier', $data)) {
            if ($data['id_calendrier'] === null) {
                $calendrierJour->setCalendrier(null);
            } else {
                $calendrier = $calendrierRepository->find($data['id_calendrier']);
                if (!$calendrier) {
                    return $this->json(['error' => 'Calendrier introuvable.'], Response::HTTP_NOT_FOUND);
                }
                $calendrierJour->setCalendrier($calendrier);
            }
        }

        $em->flush();

        return $this->json($calendrierJour, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendrier_jour_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Jour de calendrier supprimé')]
    #[OA\Response(response: 404, description: 'Jour introuvable')]
    public function delete(CalendrierJour $calendrierJour, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($calendrierJour);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}