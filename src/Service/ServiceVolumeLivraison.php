<?php

namespace App\Service;

use App\Entity\Vehicule;
use App\Entity\LivraisonTournee;
use App\Entity\Depot;
use App\Entity\Structure;
use App\Repository\PanierLivraisonRepository;

/**
 * Répartit les paniers entre les véhicules actifs et affecte un véhicule aux livraisons.
 */
class ServiceVolumeLivraison
{
    public function __construct(private PanierLivraisonRepository $panierRepository)
    {
    }

    /**
     * Charge les paniers non affectés depuis la base puis calcule leur répartition.
     *
     * @return array{
     *     plan: array<int, array{vehicule: Vehicule, depots: array<int, int>, charge: int}>,
     *     paniers_par_depot: array<int, list<\\App\\Entity\\PanierLivraison>>
     * }
     */
    public function planifierPourStructure(Structure $structure): array
    {
        $demandes = [];
        $paniersParDepot = [];

        foreach ($structure->getDepots() as $depot) {
            $paniers = $this->panierRepository->findPaniersAPreparerPourDepot($depot);
            $paniersParDepot[$depot->getId()] = $paniers;
            $demandes[] = ['depot' => $depot, 'nombre' => count($paniers)];
        }

        return [
            'plan' => $this->planifier($demandes, $structure->getVehicules()),
            'paniers_par_depot' => $paniersParDepot,
        ];
    }

    /**
     * Stock disponible (paniers à préparer non affectés) pour une liste de dépôts.
     *
     * @param iterable<Depot> $depots
     * @return array<int, list<\App\Entity\PanierLivraison>>
     */
    public function stockDisponible(iterable $depots): array
    {
        $stock = [];
        foreach ($depots as $depot) {
            $stock[$depot->getId()] = $this->panierRepository->findPaniersAPreparerPourDepot($depot);
        }

        return $stock;
    }

    /**
     * Sélectionne les paniers réellement concernés par une demande ciblée.
     *
     * @param array<int, array{depot: Depot, nombre: int}> $demandes
     * @return array<int, list<\App\Entity\PanierLivraison>>
     */
    public function paniersPourDemandes(array $demandes): array
    {
        $stock = $this->stockDisponible(array_map(
            static fn (array $demande): Depot => $demande['depot'],
            $demandes
        ));

        $paniers = [];
        foreach ($demandes as $demande) {
            $depotId = $demande['depot']->getId();
            $disponibles = $stock[$depotId] ?? [];
            if (count($disponibles) < $demande['nombre']) {
                throw new \DomainException(sprintf(
                    'Il ne reste que %d paniers disponibles pour %s (%d demandés).',
                    count($disponibles),
                    $demande['depot']->getNom(),
                    $demande['nombre']
                ));
            }

            $paniers[$depotId] = array_slice($disponibles, 0, $demande['nombre']);
        }

        return $paniers;
    }

    /**
     * Répartit les quantités par dépôt dans la flotte active.
     * Les demandes sont un tableau de lignes ['depot' => Depot, 'nombre' => int].
     *
     * @param array<int, array{depot: Depot, nombre: int}> $demandes
     * @param iterable<Vehicule> $vehicules
     * @return array<int, array{vehicule: Vehicule, depots: array<int, int>, charge: int}>
     */
    public function planifier(array $demandes, iterable $vehicules): array
    {
        $flotte = [];
        foreach ($vehicules as $vehicule) {
            if ($vehicule->isActif() && $vehicule->getCapacitePanier() !== null
                && $vehicule->getCapacitePanier() > 0) {
                $flotte[] = ['vehicule' => $vehicule, 'reste' => $vehicule->getCapacitePanier(), 'depots' => []];
            }
        }

        foreach ($demandes as $demande) {
            $depot = $demande['depot'];
            $aLivrer = $demande['nombre'];
            $capaciteDepot = $depot->getCapaciteMaxPaniers();

            if ($aLivrer < 0) {
                throw new \InvalidArgumentException('Le nombre de paniers ne peut pas être négatif.');
            }
            if ($capaciteDepot !== null && $aLivrer > $capaciteDepot) {
                throw new \DomainException(sprintf(
                    'Le dépôt %s a %d paniers à livrer, au-delà de sa capacité de %d.',
                    $depot->getNom(), $aLivrer, $capaciteDepot
                ));
            }
        }

        if (array_sum(array_column($demandes, 'nombre')) === 0) {
            return [];
        }
        if ($flotte === []) {
            throw new \DomainException('Aucun véhicule actif avec une capacité valide n’est disponible.');
        }

        usort($demandes, static fn (array $a, array $b): int => $b['nombre'] <=> $a['nombre']);
        foreach ($demandes as $demande) {
            $resteDepot = $demande['nombre'];
            $depotId = $demande['depot']->getId();

            while ($resteDepot > 0) {
                usort($flotte, static fn (array $a, array $b): int => $a['reste'] <=> $b['reste']);
                $index = array_key_last($flotte);
                if ($flotte[$index]['reste'] <= 0) {
                    throw new \DomainException(sprintf(
                        'Capacité insuffisante : il reste %d paniers à répartir.',
                        $resteDepot
                    ));
                }

                foreach ($flotte as $candidate => $camionnette) {
                    if ($camionnette['reste'] >= $resteDepot) {
                        $index = $candidate;
                        break;
                    }
                }

                $quantite = min($resteDepot, $flotte[$index]['reste']);
                $flotte[$index]['depots'][$depotId] = ($flotte[$index]['depots'][$depotId] ?? 0) + $quantite;
                $flotte[$index]['reste'] -= $quantite;
                $resteDepot -= $quantite;
            }
        }

        $plan = [];
        foreach ($flotte as $camionnette) {
            if ($camionnette['depots'] === []) {
                continue;
            }
            $capacite = $camionnette['vehicule']->getCapacitePanier();
            $plan[] = [
                'vehicule' => $camionnette['vehicule'],
                'depots' => $camionnette['depots'],
                'charge' => $capacite - $camionnette['reste'],
            ];
        }

        return $plan;
    }

    /**
     * @param iterable<Vehicule> $vehicules
     */
    public function choisirVehicule(iterable $vehicules, int $nombrePaniers): ?Vehicule
    {
        if ($nombrePaniers < 0) {
            throw new \InvalidArgumentException('Le nombre de paniers ne peut pas être négatif.');
        }

        $vehiculesAdaptes = [];
        foreach ($vehicules as $vehicule) {
            $capacite = $vehicule->getCapacitePanier();
            if ($vehicule->isActif() && $capacite !== null && $capacite >= $nombrePaniers) {
                $vehiculesAdaptes[] = $vehicule;
            }
        }

        usort(
            $vehiculesAdaptes,
            static fn (Vehicule $a, Vehicule $b): int => $a->getCapacitePanier() <=> $b->getCapacitePanier()
        );

        return $vehiculesAdaptes[0] ?? null;
    }

    /**
     * Choisit et affecte le véhicule de la structure de la tournée.
     *
     * @throws \DomainException si la tournée ou un véhicule adapté manque
     */
    public function affecterVehicule(LivraisonTournee $livraison, int $nombrePaniers): Vehicule
    {
        $structure = $livraison->getTournee()?->getStructure();
        if ($structure === null) {
            throw new \DomainException('La tournée doit être associée à une structure.');
        }

        $vehicule = $this->choisirVehicule($structure->getVehicules(), $nombrePaniers);
        if ($vehicule === null) {
            throw new \DomainException(sprintf(
                'Aucun véhicule actif ne peut transporter %d paniers.',
                $nombrePaniers
            ));
        }

        $livraison->setVehicule($vehicule);

        return $vehicule;
    }
}

