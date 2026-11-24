<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Depot;
use App\Repository\AbonnementRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class AbonnementReservationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AbonnementRepository $abonnementRepository,
    ) {
    }

    /**
     * Enregistre un abonnement en réservant atomiquement une place au dépôt.
     * La capacité du dépôt est exprimée en nombre de paniers.
     */
    public function creer(Abonnement $abonnement): Abonnement
    {
        $depot = $abonnement->getDepot();
        $debut = $abonnement->getDateDebut();
        $fin = $abonnement->getDateFin();

        if ($depot === null || $debut === null || $fin === null) {
            throw new \InvalidArgumentException('Le dépôt et les dates de l’abonnement sont obligatoires.');
        }
        if ($fin < $debut) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($abonnement, $depot, $debut, $fin): Abonnement {
            // Toutes les créations pour ce dépôt sont sérialisées sur la même ligne.
            $entityManager->lock($depot, LockMode::PESSIMISTIC_WRITE);

            $capacite = $depot->getCapaciteMaxPaniers();
            if ($capacite === null || $capacite < 0) {
                throw new \DomainException('La capacité du dépôt est invalide.');
            }

            $placesReservees = $this->abonnementRepository
                ->compterActifsPourDepotEtPeriode($depot, $debut, $fin);

            if ($placesReservees + 1 > $capacite) {
                throw new \DomainException(sprintf(
                    'Le dépôt %s est complet pour cette période (%d/%d paniers réservés).',
                    $depot->getNom(),
                    $placesReservees,
                    $capacite
                ));
            }

            $entityManager->persist($abonnement);

            return $abonnement;
        });
    }
}
