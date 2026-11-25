<?php

namespace App\Controller\Api;

use App\Entity\LivraisonTournee;
use App\Service\ServiceVolumeLivraison;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/livraisons-tournees')]
class LivraisonTourneeController extends AbstractController
{
    #[Route('/{id}/affecter-vehicule', name: 'api_livraison_tournee_affecter_vehicule', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function affecterVehicule(
        int $id,
        EntityManagerInterface $entityManager,
        ServiceVolumeLivraison $vehiculeCapacityService
    ): JsonResponse {
        $livraison = $entityManager->getRepository(LivraisonTournee::class)->find($id);
        if (!$livraison) {
            return $this->json(['message' => 'Livraison introuvable.'], 404);
        }

        $nombrePaniers = $livraison->getPaniers()->count();

        try {
            $vehicule = $vehiculeCapacityService->affecterVehicule($livraison, $nombrePaniers);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Véhicule affecté à la livraison.',
            'livraison_id' => $livraison->getId(),
            'nombre_paniers' => $nombrePaniers,
            'vehicule' => [
                'id' => $vehicule->getId(),
                'immatriculation' => $vehicule->getImmatriculation(),
                'capacite_paniers' => $vehicule->getCapacitePanier(),
            ],
        ]);
    }
}
