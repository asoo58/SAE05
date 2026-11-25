<?php

namespace App\Controller\Api;

use App\Entity\Abonnement;
use App\Entity\Calendrier;
use App\Entity\Depot;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\AdherentRepository;
use App\Service\AbonnementReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/abonnements')]
class AbonnementController extends AbstractController
{
    #[Route('', name: 'api_abonnements_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        AdherentRepository $adherentRepository,
        AbonnementReservationService $reservationService
    ): JsonResponse {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['message' => 'Compte utilisateur invalide.'], 401);
        }

        $adherent = $adherentRepository->findOneBy(['utilisateur' => $utilisateur]);
        if ($adherent === null) {
            return $this->json(['message' => 'Le compte connecté ne possède pas de profil adhérent.'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['message' => 'Le corps de la requête doit être un JSON valide.'], 400);
        }

        if (!is_array($data)
            || !isset($data['date_debut'], $data['date_fin'], $data['produit_id'], $data['calendrier_id'], $data['depot_id'])) {
            return $this->json([
                'message' => 'Champs requis : date_debut, date_fin, produit_id, calendrier_id et depot_id.',
            ], 400);
        }

        $dateDebut = $this->parseDate($data['date_debut']);
        $dateFin = $this->parseDate($data['date_fin']);
        if ($dateDebut === null || $dateFin === null) {
            return $this->json(['message' => 'Les dates doivent respecter le format YYYY-MM-DD.'], 400);
        }
        if ($dateFin < $dateDebut) {
            return $this->json(['message' => 'La date de fin doit être postérieure ou égale à la date de début.'], 400);
        }

        foreach (['produit_id', 'calendrier_id', 'depot_id'] as $idField) {
            if (!is_int($data[$idField]) && !(is_string($data[$idField]) && ctype_digit($data[$idField]))) {
                return $this->json(['message' => sprintf('%s doit être un entier.', $idField)], 400);
            }
        }

        $produit = $entityManager->getRepository(Produit::class)->find((int) $data['produit_id']);
        $calendrier = $entityManager->getRepository(Calendrier::class)->find((int) $data['calendrier_id']);
        $depot = $entityManager->getRepository(Depot::class)->find((int) $data['depot_id']);
        if ($produit === null || $calendrier === null || $depot === null) {
            return $this->json(['message' => 'Produit, calendrier ou dépôt introuvable.'], 404);
        }

        $structure = $utilisateur->getStructure();
        if ($depot->getStructure()?->getId() !== $structure?->getId()
            || $calendrier->getTournee()?->getStructure()?->getId() !== $structure?->getId()) {
            return $this->json(['message' => 'Le dépôt et le calendrier doivent appartenir à votre structure.'], 403);
        }

        $abonnement = (new Abonnement())
            ->setAdherent($adherent)
            ->setProduit($produit)
            ->setCalendrier($calendrier)
            ->setDepot($depot)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin);

        try {
            $reservationService->creer($abonnement);
        } catch (\DomainException | \InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'message' => 'Abonnement créé et place réservée au dépôt.',
            'abonnement_id' => $abonnement->getId(),
            'depot_id' => $depot->getId(),
            'capacite_max_paniers' => $depot->getCapaciteMaxPaniers(),
        ], 201);
    }

    private function parseDate(mixed $value): ?\DateTime
    {
        if (!is_string($value)) {
            return null;
        }

        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        $errors = \DateTime::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }
}
