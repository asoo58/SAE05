<?php

namespace App\Service;

use App\Entity\Chauffeur;
use App\Entity\Depot;
use App\Entity\EtapeTournee;
use App\Entity\LivraisonTournee;
use App\Entity\Structure;
use App\Entity\Tournee;
use App\Service\Exception\ChauffeurIntrouvableException;
use App\Service\Exception\ChauffeursRequisException;
use App\Service\Exception\OrganisationConcurrenteException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calcule un plan de tournées et crée les tournées correspondantes.
 *
 * Utilisé par l'API protégée et par la page publique d'organisation.
 */
class OrganisationTourneesService
{
    /**
     * Espace de nommage du verrou consultatif PostgreSQL.
     * Deux créations simultanées pour la même structure sont sérialisées.
     */
    private const VERROU_NAMESPACE = 728411;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceVolumeLivraison $planificateur,
    ) {
    }

    /**
     * Aperçu du plan sans rien enregistrer en base.
     *
     * @return array<string, mixed>
     *
     * @throws \DomainException
     */
    public function previsualiser(Structure $structure): array
    {
        [$plan, $paniersParDepot] = $this->calculerPlan($structure);

        $vehicules = [];
        $capaciteTotale = 0;
        $chargeTotale = 0;

        foreach ($plan as $affectation) {
            $vehicule = $affectation['vehicule'];
            $capacite = (int) ($vehicule->getCapacitePanier() ?? 0);
            $charge = (int) $affectation['charge'];

            $capaciteTotale += $capacite;
            $chargeTotale += $charge;

            $etapes = [];
            foreach ($this->ordonnerDepotsDeLAffectation($structure, $affectation['depots']) as $index => $depot) {
                $etapes[] = [
                    'ordre' => $index + 1,
                    'depot_id' => $depot->getId(),
                    'nom' => $depot->getNom(),
                    'ville' => $depot->getVille(),
                    'quantite' => (int) ($affectation['depots'][$depot->getId()] ?? 0),
                ];
            }

            $vehicules[] = [
                'vehicule_id' => $vehicule->getId(),
                'immatriculation' => $vehicule->getImmatriculation(),
                'modele' => $vehicule->getNomModel(),
                'capacite' => $capacite,
                'charge' => $charge,
                'reste' => max(0, $capacite - $charge),
                'taux' => $capacite > 0 ? (int) round($charge * 100 / $capacite) : 0,
                'etapes' => $etapes,
            ];
        }

        $depots = [];
        $paniersTotal = 0;
        foreach ($structure->getDepots() as $depot) {
            $nombre = count($paniersParDepot[$depot->getId()] ?? []);
            $paniersTotal += $nombre;
            $capaciteMax = $depot->getCapaciteMaxPaniers();

            $depots[] = [
                'depot_id' => $depot->getId(),
                'nom' => $depot->getNom(),
                'ville' => $depot->getVille(),
                'paniers' => $nombre,
                'capacite_max' => $capaciteMax,
                'taux' => ($capaciteMax !== null && $capaciteMax > 0)
                    ? (int) round($nombre * 100 / $capaciteMax)
                    : null,
            ];
        }

        return [
            'structure' => [
                'id' => $structure->getId(),
                'nom' => $structure->getNomCommercial(),
            ],
            'paniers_total' => $paniersTotal,
            'vehicules_total' => count($vehicules),
            'capacite_totale' => $capaciteTotale,
            'charge_totale' => $chargeTotale,
            'taux_remplissage' => $capaciteTotale > 0
                ? (int) round($chargeTotale * 100 / $capaciteTotale)
                : 0,
            'paniers_par_depot' => $depots,
            'vehicules' => $vehicules,
        ];
    }

    /**
     * Version historique : un chauffeur par véhicule, dans l'ordre du plan.
     *
     * @param list<int|string> $chauffeursIds
     *
     * @return array{date: \DateTimeImmutable, tournees: array<int, array<string, mixed>>}
     *
     * @throws ChauffeursRequisException
     * @throws ChauffeurIntrouvableException
     * @throws \DomainException
     * @throws \InvalidArgumentException
     */
    public function organiser(Structure $structure, \DateTimeImmutable $date, array $chauffeursIds): array
    {
        return $this->dansTransactionVerrouillee($structure, function () use ($structure, $date, $chauffeursIds): array {
            [$plan, $paniersParDepot] = $this->calculerPlan($structure);

            if ($plan === []) {
                throw new \DomainException('Aucun panier à livrer pour cette structure.');
            }

            if (count($chauffeursIds) !== count($plan)) {
                $exception = new ChauffeursRequisException(
                    'Fournissez un identifiant de chauffeur par camionnette utilisée.'
                );
                $exception->setVehiculesAAffecter(array_map(
                    static fn (array $affectation): int => $affectation['vehicule']->getId(),
                    $plan
                ));

                throw $exception;
            }

            $affectations = [];
            foreach ($plan as $index => $affectation) {
                $affectations[$affectation['vehicule']->getId()] = $chauffeursIds[$index];
            }

            return $this->creerTournees($structure, $date, $affectations, $plan, $paniersParDepot);
        });
    }

    /**
     * Crée les tournées à partir d'une affectation explicite véhicule => chauffeur.
     *
     * @param array<int|string, int|string> $chauffeursParVehicule
     * @param array<int|string, int|string>|null $planAttendu Nombre de paniers par dépôt
     *        tel qu'affiché à l'utilisateur, pour détecter un plan devenu obsolète.
     *
     * @return array{date: \DateTimeImmutable, tournees: array<int, array<string, mixed>>}
     *
     * @throws ChauffeursRequisException
     * @throws ChauffeurIntrouvableException
     * @throws OrganisationConcurrenteException
     * @throws \DomainException
     * @throws \InvalidArgumentException
     */
    public function organiserAvecAffectations(
        Structure $structure,
        \DateTimeImmutable $date,
        array $chauffeursParVehicule,
        ?array $planAttendu = null
    ): array {
        return $this->dansTransactionVerrouillee($structure, function () use (
            $structure,
            $date,
            $chauffeursParVehicule,
            $planAttendu
        ): array {
            [$plan, $paniersParDepot] = $this->calculerPlan($structure);

            $this->verifierPlanAttendu($planAttendu, $paniersParDepot);

            if ($plan === []) {
                throw new \DomainException('Aucun panier à livrer pour cette structure.');
            }

            $affectations = [];
            $vehiculesSansChauffeur = [];

            foreach ($plan as $affectation) {
                $vehiculeId = $affectation['vehicule']->getId();
                $chauffeurId = $chauffeursParVehicule[$vehiculeId]
                    ?? $chauffeursParVehicule[(string) $vehiculeId]
                    ?? null;

                if ($chauffeurId === null || $chauffeurId === '') {
                    $vehiculesSansChauffeur[] = $vehiculeId;
                    continue;
                }

                $affectations[$vehiculeId] = $chauffeurId;
            }

            if ($vehiculesSansChauffeur !== []) {
                $exception = new ChauffeursRequisException(
                    'Sélectionnez un chauffeur pour chaque véhicule utilisé.'
                );
                $exception->setVehiculesAAffecter($vehiculesSansChauffeur);

                throw $exception;
            }

            return $this->creerTournees($structure, $date, $affectations, $plan, $paniersParDepot);
        });
    }

    /**
     * Aperçu d'une demande ciblée : l'utilisateur choisit les dépôts et les quantités.
     *
     * @param array<int, array{depot_id: int|string, quantite: int|string}> $lignes
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     * @throws \DomainException
     */
    public function previsualiserCible(Structure $structure, \DateTimeImmutable $date, array $lignes): array
    {
        [$demandes, $lignesDetail, $depots] = $this->resoudreDemandesCiblees($structure, $lignes);
        $stockParDepot = $this->planificateur->stockDisponible($depots);
        $lignesDetail = $this->verifierStockCible($demandes, $lignesDetail, $stockParDepot);
        $vehicules = $this->vehiculesDisponibles($structure, $date);

        if ($vehicules === []) {
            throw new \DomainException('Aucune camionnette n’est disponible à cette date.');
        }

        $plan = $this->planificateur->planifier($demandes, $vehicules);

        return [
            'date_livraison' => $date->format('Y-m-d'),
            'structure' => [
                'id' => $structure->getId(),
                'nom' => $structure->getNomCommercial(),
            ],
            'paniers_total' => array_sum(array_column($lignesDetail, 'quantite')),
            'lignes' => $lignesDetail,
            'plan_attendu' => $this->stockAttendu($stockParDepot),
            'vehicules' => $this->vehiculesDepuisPlan($structure, $plan),
        ];
    }

    /**
     * Crée les tournées d'une demande ciblée sous verrou, avec le même
     * contrôle de concurrence que le mode automatique.
     *
     * @param array<int, array{depot_id: int|string, quantite: int|string}> $lignes
     * @param array<int|string, int|string> $chauffeursParVehicule
     * @param array<int|string, int|string>|null $planAttendu
     *
     * @return array{date: \DateTimeImmutable, tournees: array<int, array<string, mixed>>}
     *
     * @throws ChauffeursRequisException
     * @throws ChauffeurIntrouvableException
     * @throws OrganisationConcurrenteException
     * @throws \InvalidArgumentException
     * @throws \DomainException
     */
    public function organiserCible(
        Structure $structure,
        \DateTimeImmutable $date,
        array $lignes,
        array $chauffeursParVehicule,
        ?array $planAttendu = null
    ): array {
        return $this->dansTransactionVerrouillee($structure, function () use (
            $structure,
            $date,
            $lignes,
            $chauffeursParVehicule,
            $planAttendu
        ): array {
            [$demandes, $lignesDetail, $depots] = $this->resoudreDemandesCiblees($structure, $lignes);
            $stockParDepot = $this->planificateur->stockDisponible($depots);

            // Contrôle de concurrence d'abord : un plan devenu obsolète doit
            // donner 409 (recalcul automatique), pas une erreur de stock.
            $this->verifierPlanAttendu($planAttendu, $stockParDepot);
            $this->verifierStockCible($demandes, $lignesDetail, $stockParDepot);

            $vehicules = $this->vehiculesDisponibles($structure, $date);
            if ($vehicules === []) {
                throw new \DomainException('Aucune camionnette n’est disponible à cette date.');
            }

            $plan = $this->planificateur->planifier($demandes, $vehicules);

            $affectations = [];
            $vehiculesSansChauffeur = [];
            foreach ($plan as $affectation) {
                $vehiculeId = $affectation['vehicule']->getId();
                $chauffeurId = $chauffeursParVehicule[$vehiculeId]
                    ?? $chauffeursParVehicule[(string) $vehiculeId]
                    ?? null;

                if ($chauffeurId === null || $chauffeurId === '') {
                    $vehiculesSansChauffeur[] = $vehiculeId;
                    continue;
                }

                $affectations[$vehiculeId] = $chauffeurId;
            }

            if ($vehiculesSansChauffeur !== []) {
                $exception = new ChauffeursRequisException(
                    'Sélectionnez un chauffeur pour chaque camionnette utilisée.'
                );
                $exception->setVehiculesAAffecter($vehiculesSansChauffeur);

                throw $exception;
            }

            $paniersParDepot = $this->planificateur->paniersPourDemandes($demandes);

            return $this->creerTournees($structure, $date, $affectations, $plan, $paniersParDepot);
        });
    }

    /**
     * @param array<int, array{depot_id: int|string, quantite: int|string}> $lignes
     * @return array{0: array<int, array{depot: Depot, nombre: int}>, 1: array<int, array<string, mixed>>, 2: array<int, Depot>}
     */
    private function resoudreDemandesCiblees(Structure $structure, array $lignes): array
    {
        if ($lignes === []) {
            throw new \InvalidArgumentException('Ajoute au moins un dépôt à la demande.');
        }

        $depotsParId = [];
        foreach ($structure->getDepots() as $depot) {
            $depotsParId[$depot->getId()] = $depot;
        }

        $demandes = [];
        $details = [];
        $depots = [];
        $dejaVus = [];

        foreach ($lignes as $ligne) {
            if (!is_array($ligne)) {
                throw new \InvalidArgumentException('Chaque ligne doit être un objet.');
            }

            $depotId = $ligne['depot_id'] ?? null;
            $quantite = $ligne['quantite'] ?? null;

            if (!is_int($depotId) && !(is_string($depotId) && ctype_digit($depotId))) {
                throw new \InvalidArgumentException('L’identifiant du dépôt est invalide.');
            }
            if (!is_int($quantite) && !(is_string($quantite) && ctype_digit($quantite))) {
                throw new \InvalidArgumentException('La quantité est invalide.');
            }

            $depotId = (int) $depotId;
            $quantite = (int) $quantite;

            if ($quantite <= 0) {
                throw new \InvalidArgumentException('La quantité doit être supérieure à zéro.');
            }
            if (isset($dejaVus[$depotId])) {
                throw new \InvalidArgumentException('Un dépôt ne peut figurer qu’une fois dans la demande.');
            }

            $depot = $depotsParId[$depotId] ?? null;
            if ($depot === null) {
                throw new \InvalidArgumentException('Ce dépôt n’appartient pas à la structure choisie.');
            }

            $dejaVus[$depotId] = true;
            $depots[] = $depot;
            $demandes[] = ['depot' => $depot, 'nombre' => $quantite];
            $details[] = [
                'depot_id' => $depotId,
                'nom' => $depot->getNom(),
                'ville' => $depot->getVille(),
                'quantite' => $quantite,
                'capacite_max' => $depot->getCapaciteMaxPaniers(),
            ];
        }

        return [$demandes, $details, $depots];
    }

    /**
     * Vérifie le stock disponible et la capacité de chaque dépôt demandé.
     *
     * @param array<int, array{depot: Depot, nombre: int}> $demandes
     * @param array<int, array<string, mixed>> $lignesDetail
     * @param array<int, list<\App\Entity\PanierLivraison>> $stockParDepot
     * @return array<int, array<string, mixed>>
     */
    private function verifierStockCible(array $demandes, array $lignesDetail, array $stockParDepot): array
    {
        foreach ($demandes as $demande) {
            $depot = $demande['depot'];
            $disponible = count($stockParDepot[$depot->getId()] ?? []);

            if ($demande['nombre'] > $disponible) {
                throw new \DomainException(sprintf(
                    'Il ne reste que %d paniers disponibles pour %s (%d demandés).',
                    $disponible,
                    $depot->getNom(),
                    $demande['nombre']
                ));
            }

            $capacite = $depot->getCapaciteMaxPaniers();
            if ($capacite !== null && $demande['nombre'] > $capacite) {
                throw new \DomainException(sprintf(
                    '%s ne peut recevoir que %d paniers (%d demandés).',
                    $depot->getNom(),
                    $capacite,
                    $demande['nombre']
                ));
            }
        }

        foreach ($lignesDetail as $index => $detail) {
            $lignesDetail[$index]['stock_disponible'] = count($stockParDepot[$detail['depot_id']] ?? []);
        }

        return $lignesDetail;
    }

    /**
     * @param array<int, list<\App\Entity\PanierLivraison>> $stockParDepot
     * @return array<int, int>
     */
    private function stockAttendu(array $stockParDepot): array
    {
        $attendu = [];
        foreach ($stockParDepot as $depotId => $paniers) {
            $attendu[$depotId] = count($paniers);
        }

        return $attendu;
    }

    /**
     * @param array<int, array{vehicule: \App\Entity\Vehicule, depots: array<int, int>, charge: int}> $plan
     * @return array<int, array<string, mixed>>
     */
    private function vehiculesDepuisPlan(Structure $structure, array $plan): array
    {
        $vehicules = [];

        foreach ($plan as $affectation) {
            $vehicule = $affectation['vehicule'];
            $capacite = (int) ($vehicule->getCapacitePanier() ?? 0);
            $charge = (int) $affectation['charge'];

            $etapes = [];
            foreach ($this->ordonnerDepotsDeLAffectation($structure, $affectation['depots']) as $index => $depot) {
                $etapes[] = [
                    'ordre' => $index + 1,
                    'depot_id' => $depot->getId(),
                    'nom' => $depot->getNom(),
                    'ville' => $depot->getVille(),
                    'quantite' => (int) ($affectation['depots'][$depot->getId()] ?? 0),
                ];
            }

            $vehicules[] = [
                'vehicule_id' => $vehicule->getId(),
                'immatriculation' => $vehicule->getImmatriculation(),
                'modele' => $vehicule->getNomModel(),
                'capacite' => $capacite,
                'charge' => $charge,
                'reste' => max(0, $capacite - $charge),
                'taux' => $capacite > 0 ? (int) round($charge * 100 / $capacite) : 0,
                'etapes' => $etapes,
            ];
        }

        return $vehicules;
    }

    /**
     * Camionnettes de la structure déjà affectées à une livraison ce jour-là.
     *
     * @return array<int, \App\Entity\Vehicule>
     */
    private function vehiculesDisponibles(Structure $structure, \DateTimeImmutable $date): array
    {
        $occupes = array_map('intval', $this->entityManager->createQuery(
            'SELECT IDENTITY(l.vehicule) FROM App\Entity\LivraisonTournee l WHERE l.date_livraison = :date'
        )->setParameter('date', new \DateTime($date->format('Y-m-d')))->getSingleColumnResult());

        $disponibles = [];
        foreach ($structure->getVehicules() as $vehicule) {
            if (!in_array((int) $vehicule->getId(), $occupes, true)) {
                $disponibles[] = $vehicule;
            }
        }

        return $disponibles;
    }

    public function nomChauffeur(Chauffeur $chauffeur): string
    {
        $employe = $chauffeur->getEmploye();
        $nom = trim(sprintf(
            '%s %s',
            $employe?->getPrenom() ?? '',
            $employe?->getNom() ?? ''
        ));

        if ($nom !== '') {
            return $nom;
        }

        return sprintf('Chauffeur #%d', (int) $chauffeur->getId());
    }

    /**
     * Exécute une création dans une transaction et verrouille la structure
     * pour empêcher deux utilisateurs de consommer les mêmes paniers.
     */
    private function dansTransactionVerrouillee(Structure $structure, callable $callback): mixed
    {
        $connection = $this->entityManager->getConnection();
        $transactionOuverteIci = !$connection->isTransactionActive();

        if ($transactionOuverteIci) {
            $connection->beginTransaction();
        }

        try {
            $this->verrouillerStructure($connection, $structure);
            $resultat = $callback();

            if ($transactionOuverteIci) {
                $connection->commit();
            }

            return $resultat;
        } catch (\Throwable $exception) {
            if ($transactionOuverteIci && $connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function verrouillerStructure(Connection $connection, Structure $structure): void
    {
        // Verrou consultatif PostgreSQL : la seconde création attend la fin de la première.
        // Le verrou est libéré automatiquement au commit ou au rollback.
        if (!$connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }

        $connection->executeQuery(
            'SELECT pg_advisory_xact_lock(:namespace, :cle)',
            [
                'namespace' => self::VERROU_NAMESPACE,
                'cle' => (int) $structure->getId(),
            ],
            [
                'namespace' => ParameterType::INTEGER,
                'cle' => ParameterType::INTEGER,
            ]
        );
    }

    /**
     * Vérifie que les paniers disponibles correspondent encore au plan
     * affiché à l'utilisateur avant de créer les tournées.
     *
     * @param array<int|string, int|string>|null $planAttendu
     * @param array<int, list<\App\Entity\PanierLivraison>> $paniersParDepot
     */
    private function verifierPlanAttendu(?array $planAttendu, array $paniersParDepot): void
    {
        if ($planAttendu === null) {
            return;
        }

        $attendu = [];
        foreach ($planAttendu as $depotId => $nombre) {
            if (!is_int($depotId) && !(is_string($depotId) && ctype_digit($depotId))) {
                throw new \InvalidArgumentException('Le plan attendu est invalide.');
            }
            if (!is_int($nombre) && !(is_string($nombre) && ctype_digit($nombre))) {
                throw new \InvalidArgumentException('Le plan attendu est invalide.');
            }

            $attendu[(int) $depotId] = (int) $nombre;
        }

        foreach ($attendu as $depotId => $nombre) {
            $reel = count($paniersParDepot[$depotId] ?? []);
            if ($reel !== $nombre) {
                throw new OrganisationConcurrenteException(
                    'Un autre utilisateur a organisé une tournée entre-temps : le nombre de paniers disponibles a changé. Recalculez le plan avant de créer les tournées.'
                );
            }
        }
    }

    /**
     * @return array{0: array<int, array{vehicule: \App\Entity\Vehicule, depots: array<int, int>, charge: int}>, 1: array<int, list<\App\Entity\PanierLivraison>>}
     */
    private function calculerPlan(Structure $structure): array
    {
        $planification = $this->planificateur->planifierPourStructure($structure);

        return [$planification['plan'], $planification['paniers_par_depot']];
    }

    /**
     * @param array<int|string, int|string> $chauffeursParVehicule
     * @param array<int, array{vehicule: \App\Entity\Vehicule, depots: array<int, int>, charge: int}> $plan
     * @param array<int, list<\App\Entity\PanierLivraison>> $paniersParDepot
     *
     * @return array{date: \DateTimeImmutable, tournees: array<int, array<string, mixed>>}
     */
    private function creerTournees(
        Structure $structure,
        \DateTimeImmutable $date,
        array $chauffeursParVehicule,
        array $plan,
        array $paniersParDepot
    ): array {
        $chauffeurs = $this->chargerChauffeurs($chauffeursParVehicule);

        $livraisonsCreees = [];

        foreach ($plan as $affectation) {
            $vehicule = $affectation['vehicule'];
            $chauffeurId = (int) $chauffeursParVehicule[$vehicule->getId()];
            $chauffeur = $chauffeurs[$chauffeurId];

            $tournee = (new Tournee())
                ->setCodeTournee($this->genererCodeTournee($date))
                ->setNom(sprintf('Tournée %s - %s', $date->format('d/m/Y'), $vehicule->getImmatriculation()))
                ->setStructure($structure);

            $depotsOrdonnes = $this->ordonnerDepotsDeLAffectation($structure, $affectation['depots']);
            foreach ($depotsOrdonnes as $ordre => $depot) {
                $etape = (new EtapeTournee())
                    ->setTournee($tournee)
                    ->setDepot($depot)
                    ->setOrdrePassage($ordre + 1);
                $tournee->addEtapeTournee($etape);
                $this->entityManager->persist($etape);
            }

            $livraison = (new LivraisonTournee())
                ->setTournee($tournee)
                ->setVehicule($vehicule)
                ->setChauffeur($chauffeur)
                ->setDateLivraison(new \DateTime($date->format('Y-m-d')));

            foreach ($affectation['depots'] as $depotId => $nombrePaniers) {
                $aAffecter = array_splice($paniersParDepot[$depotId], 0, $nombrePaniers);
                if (count($aAffecter) !== $nombrePaniers) {
                    throw new \DomainException(
                        'Le nombre de paniers disponibles a changé pendant la planification.'
                    );
                }
                foreach ($aAffecter as $panier) {
                    $livraison->addPanier($panier);
                }
            }

            $this->entityManager->persist($tournee);
            $this->entityManager->persist($livraison);

            $livraisonsCreees[] = [
                'livraison' => $livraison,
                'tournee' => $tournee,
                'vehicule' => $vehicule,
                'chauffeur' => $chauffeur,
                'charge' => $affectation['charge'],
                'depots' => $depotsOrdonnes,
                'quantites' => $affectation['depots'],
            ];
        }

        $this->entityManager->flush();

        $resultat = [];
        foreach ($livraisonsCreees as $element) {
            $resultat[] = [
                'livraison_id' => $element['livraison']->getId(),
                'tournee_id' => $element['tournee']->getId(),
                'vehicule_id' => $element['vehicule']->getId(),
                'vehicule_immatriculation' => $element['vehicule']->getImmatriculation(),
                'chauffeur_id' => $element['chauffeur']->getId(),
                'chauffeur_nom' => $this->nomChauffeur($element['chauffeur']),
                'charge' => $element['charge'],
                'capacite_vehicule' => $element['vehicule']->getCapacitePanier(),
                'etapes' => array_map(
                    fn ($depot, int $index): array => [
                        'ordre' => $index + 1,
                        'depot_id' => $depot->getId(),
                        'nom' => $depot->getNom(),
                        'quantite' => (int) ($element['quantites'][$depot->getId()] ?? 0),
                    ],
                    $element['depots'],
                    array_keys($element['depots'])
                ),
            ];
        }

        return [
            'date' => $date,
            'tournees' => $resultat,
        ];
    }

    /**
     * @param array<int|string, int|string> $chauffeursParVehicule
     *
     * @return array<int, Chauffeur>
     */
    private function chargerChauffeurs(array $chauffeursParVehicule): array
    {
        $chauffeurs = [];

        foreach ($chauffeursParVehicule as $chauffeurId) {
            if (!is_int($chauffeurId) && !(is_string($chauffeurId) && ctype_digit($chauffeurId))) {
                throw new \InvalidArgumentException('Chaque identifiant de chauffeur doit être un entier.');
            }

            $chauffeurId = (int) $chauffeurId;
            if (isset($chauffeurs[$chauffeurId])) {
                throw new \InvalidArgumentException('Un chauffeur ne peut être affecté qu’à une camionnette.');
            }

            $chauffeur = $this->entityManager->getRepository(Chauffeur::class)->find($chauffeurId);
            if ($chauffeur === null) {
                throw new ChauffeurIntrouvableException(sprintf('Chauffeur %d introuvable.', $chauffeurId));
            }

            $chauffeurs[$chauffeurId] = $chauffeur;
        }

        return $chauffeurs;
    }

    private function genererCodeTournee(\DateTimeImmutable $date): string
    {
        return 'A' . $date->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }

    /**
     * Ordonne les dépôts d'une affectation (clé = identifiant de dépôt).
     *
     * @param array<int, int> $quantitesParDepot
     *
     * @return list<Depot>
     */
    private function ordonnerDepotsDeLAffectation(Structure $structure, array $quantitesParDepot): array
    {
        $depots = [];
        foreach ($structure->getDepots() as $depot) {
            if (array_key_exists($depot->getId(), $quantitesParDepot)) {
                $depots[] = $depot;
            }
        }

        return $this->ordonnerDepots($depots, $structure);
    }

    /**
     * @param list<Depot> $depots
     *
     * @return list<Depot>
     */
    private function ordonnerDepots(array $depots, Structure $structure): array
    {
        $departLatitude = $structure->getPointDepartLat();
        $departLongitude = $structure->getPointDepartLng();

        if ($departLatitude === null || $departLongitude === null) {
            usort($depots, static fn ($a, $b): int => $a->getId() <=> $b->getId());

            return $depots;
        }

        $ordonnes = [];
        $latitudeActuelle = (float) $departLatitude;
        $longitudeActuelle = (float) $departLongitude;

        while ($depots !== []) {
            $meilleurIndex = null;
            $meilleureDistance = INF;

            foreach ($depots as $index => $depot) {
                if ($depot->getLatitude() === null || $depot->getLongitude() === null) {
                    continue;
                }

                $distance = $this->distance(
                    $latitudeActuelle,
                    $longitudeActuelle,
                    (float) $depot->getLatitude(),
                    (float) $depot->getLongitude()
                );

                if ($distance < $meilleureDistance) {
                    $meilleureDistance = $distance;
                    $meilleurIndex = $index;
                }
            }

            if ($meilleurIndex === null) {
                usort($depots, static fn ($a, $b): int => $a->getId() <=> $b->getId());
                array_push($ordonnes, ...$depots);
                break;
            }

            $prochain = $depots[$meilleurIndex];
            $ordonnes[] = $prochain;
            array_splice($depots, $meilleurIndex, 1);
            $latitudeActuelle = (float) $prochain->getLatitude();
            $longitudeActuelle = (float) $prochain->getLongitude();
        }

        return $ordonnes;
    }

    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);
        $a = sin($latDiff / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDiff / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
