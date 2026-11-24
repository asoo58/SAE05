<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Entity\Depot;
use App\Entity\LivraisonTournee;
use App\Entity\PanierLivraison;
use App\Entity\Structure;
use App\Service\Exception\ChauffeurIntrouvableException;
use App\Service\Exception\ChauffeursRequisException;
use App\Service\Exception\OrganisationConcurrenteException;
use App\Service\OrganisationTourneesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Outil d'organisation des tournées.
 *
 * Volontairement accessible sans authentification : cette page sert d'outil
 * de travail et ne remplace pas l'API protégée (/api/structures/{id}/organiser-tournees).
 */
final class TourneesController extends AbstractController
{
    #[Route('/tournees', name: 'app_tournees', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, OrganisationTourneesService $organisateur): Response
    {
        $structures = $entityManager->getRepository(Structure::class)->findBy([], ['id' => 'ASC']);
        $chauffeurs = $entityManager->getRepository(Chauffeur::class)->findAll();

        $depotsParStructure = [];
        foreach ($entityManager->getRepository(Depot::class)->findBy([], ['nom' => 'ASC']) as $depot) {
            $depotsParStructure[(int) $depot->getStructure()?->getId()][] = [
                'id' => $depot->getId(),
                'nom' => $depot->getNom(),
                'ville' => $depot->getVille(),
                'capacite_max' => $depot->getCapaciteMaxPaniers(),
                'stock' => $entityManager->getRepository(PanierLivraison::class)
                    ->countPaniersAPreparerPourDepot($depot),
            ];
        }

        return $this->render('tournees/index.html.twig', [
            'structures' => $structures,
            'depots_par_structure' => $depotsParStructure,
            'chauffeurs_json' => array_map(
                fn (Chauffeur $chauffeur): array => [
                    'id' => $chauffeur->getId(),
                    'nom' => $organisateur->nomChauffeur($chauffeur),
                    'permis' => $chauffeur->getNumeroPermis(),
                ],
                $chauffeurs
            ),
        ]);
    }

    #[Route('/tournees/liste', name: 'app_tournees_liste', methods: ['GET'])]
    public function liste(
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): Response {
        $structureFiltre = $request->query->get('structure');
        $dateFiltre = $request->query->get('date');

        $structure = null;
        if (is_string($structureFiltre) && ctype_digit($structureFiltre)) {
            $structure = $entityManager->getRepository(Structure::class)->find((int) $structureFiltre);
            if ($structure === null) {
                $structureFiltre = null;
            }
        } else {
            $structureFiltre = null;
        }

        $date = null;
        if (is_string($dateFiltre) && $dateFiltre !== '') {
            $candidate = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateFiltre);
            $erreurs = \DateTimeImmutable::getLastErrors();
            if ($candidate !== false
                && ($erreurs === false || ($erreurs['warning_count'] === 0 && $erreurs['error_count'] === 0))) {
                $date = $candidate;
            } else {
                $dateFiltre = null;
            }
        } else {
            $dateFiltre = null;
        }

        $livraisons = $entityManager->getRepository(LivraisonTournee::class)
            ->findPourListe($structure, $date);

        $tournees = [];
        $groupes = [];
        $paniersTotal = 0;
        $vehicules = [];
        $chauffeurs = [];

        foreach ($livraisons as $livraison) {
            $tournee = $livraison->getTournee();
            $chauffeur = $livraison->getChauffeur();
            $vehicule = $livraison->getVehicule();
            $charge = $livraison->getPaniers()->count();
            $capacite = $vehicule?->getCapacitePanier();

            $etapes = $tournee?->getEtapeTournees()->toArray() ?? [];
            usort($etapes, static fn ($a, $b): int => $a->getOrdrePassage() <=> $b->getOrdrePassage());

            $ligne = [
                'tournee_id' => $tournee?->getId(),
                'livraison_id' => $livraison->getId(),
                'code' => $tournee?->getCodeTournee(),
                'nom' => $tournee?->getNom(),
                'structure' => $tournee?->getStructure()?->getNomCommercial(),
                'date' => $livraison->getDateLivraison(),
                'date_cle' => $livraison->getDateLivraison()?->format('Y-m-d') ?? '0000-00-00',
                'statut' => $livraison->getStatut() ?? 'planifiee',
                'vehicule' => $vehicule?->getImmatriculation(),
                'vehicule_modele' => $vehicule?->getNomModel(),
                'chauffeur' => $chauffeur !== null ? $organisateur->nomChauffeur($chauffeur) : 'Non affecté',
                'charge' => $charge,
                'capacite' => (int) ($capacite ?? 0),
                'taux' => $capacite !== null && $capacite > 0
                    ? (int) round($charge * 100 / $capacite)
                    : 0,
                'etapes' => array_map(
                    static fn ($etape): array => [
                        'ordre' => $etape->getOrdrePassage(),
                        'depot' => $etape->getDepot()?->getNom() ?? 'Dépôt supprimé',
                        'ville' => $etape->getDepot()?->getVille(),
                    ],
                    $etapes
                ),
            ];

            $tournees[] = $ligne;
            $groupes[$ligne['date_cle']][] = $ligne;
            $paniersTotal += $charge;

            if ($ligne['vehicule'] !== null) {
                $vehicules[$ligne['vehicule']] = true;
            }
            if ($chauffeur !== null) {
                $chauffeurs[(int) $chauffeur->getId()] = true;
            }
        }

        krsort($groupes);

        return $this->render('tournees/liste.html.twig', [
            'structures' => $entityManager->getRepository(Structure::class)->findBy([], ['id' => 'ASC']),
            'tournees' => $tournees,
            'groupes' => $groupes,
            'filtres' => [
                'structure' => $structureFiltre,
                'date' => $dateFiltre,
            ],
            'stats' => [
                'tournees' => count($tournees),
                'paniers' => $paniersTotal,
                'vehicules' => count($vehicules),
                'chauffeurs' => count($chauffeurs),
            ],
        ]);
    }

    #[Route('/tournees/plan', name: 'app_tournees_plan', methods: ['POST'])]
    public function calculerPlan(
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): JsonResponse {
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $structure = $this->resoudreStructure($donnees, $entityManager);
        if ($structure instanceof JsonResponse) {
            return $structure;
        }

        $date = $this->resoudreDate($donnees);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        try {
            $apercu = $organisateur->previsualiser($structure);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }

        return $this->json([
            'date_livraison' => $date->format('Y-m-d'),
            ...$apercu,
        ]);
    }

    #[Route('/tournees/creer', name: 'app_tournees_creer', methods: ['POST'])]
    public function creerTournees(
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): JsonResponse {
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $structure = $this->resoudreStructure($donnees, $entityManager);
        if ($structure instanceof JsonResponse) {
            return $structure;
        }

        $date = $this->resoudreDate($donnees);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        $affectations = $donnees['chauffeurs_par_vehicule'] ?? null;
        if (!is_array($affectations)) {
            return $this->json([
                'message' => 'Le champ chauffeurs_par_vehicule doit associer un véhicule à un chauffeur.',
            ], 400);
        }

        $planAttendu = $donnees['plan_attendu'] ?? null;
        if ($planAttendu !== null && !is_array($planAttendu)) {
            return $this->json(['message' => 'Le champ plan_attendu doit être un objet.'], 400);
        }

        try {
            $resultat = $organisateur->organiserAvecAffectations($structure, $date, $affectations, $planAttendu);
        } catch (OrganisationConcurrenteException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
                'code' => 'plan_obsolete',
            ], 409);
        } catch (ChauffeursRequisException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
                'vehicules_a_affecter' => $exception->getVehiculesAAffecter(),
            ], 400);
        } catch (ChauffeurIntrouvableException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'message' => 'Les tournées ont été créées et les paniers affectés.',
            'date_livraison' => $date->format('Y-m-d'),
            'nombre_tournees' => count($resultat['tournees']),
            'tournees' => $resultat['tournees'],
        ], 201);
    }

    #[Route('/tournees/plan-cible', name: 'app_tournees_plan_cible', methods: ['POST'])]
    public function planCible(
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): JsonResponse {
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $structure = $this->resoudreStructure($donnees, $entityManager);
        if ($structure instanceof JsonResponse) {
            return $structure;
        }

        $date = $this->resoudreDate($donnees);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        $lignes = $donnees['lignes'] ?? null;
        if (!is_array($lignes) || $lignes === []) {
            return $this->json(['message' => 'Ajoute au moins un dépôt à la demande.'], 400);
        }

        try {
            $apercu = $organisateur->previsualiserCible($structure, $date, $lignes);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }

        return $this->json($apercu);
    }

    #[Route('/tournees/creer-cible', name: 'app_tournees_creer_cible', methods: ['POST'])]
    public function creerCible(
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): JsonResponse {
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $structure = $this->resoudreStructure($donnees, $entityManager);
        if ($structure instanceof JsonResponse) {
            return $structure;
        }

        $date = $this->resoudreDate($donnees);
        if ($date instanceof JsonResponse) {
            return $date;
        }

        $lignes = $donnees['lignes'] ?? null;
        if (!is_array($lignes) || $lignes === []) {
            return $this->json(['message' => 'Ajoute au moins un dépôt à la demande.'], 400);
        }

        $affectations = $donnees['chauffeurs_par_vehicule'] ?? null;
        if (!is_array($affectations)) {
            return $this->json([
                'message' => 'Le champ chauffeurs_par_vehicule doit associer un véhicule à un chauffeur.',
            ], 400);
        }

        $planAttendu = $donnees['plan_attendu'] ?? null;
        if ($planAttendu !== null && !is_array($planAttendu)) {
            return $this->json(['message' => 'Le champ plan_attendu doit être un objet.'], 400);
        }

        try {
            $resultat = $organisateur->organiserCible($structure, $date, $lignes, $affectations, $planAttendu);
        } catch (OrganisationConcurrenteException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
                'code' => 'plan_obsolete',
            ], 409);
        } catch (ChauffeursRequisException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
                'vehicules_a_affecter' => $exception->getVehiculesAAffecter(),
            ], 400);
        } catch (ChauffeurIntrouvableException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'message' => 'La demande ciblée a été créée et les paniers affectés.',
            'date_livraison' => $date->format('Y-m-d'),
            'nombre_tournees' => count($resultat['tournees']),
            'tournees' => $resultat['tournees'],
        ], 201);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function lireJson(Request $request): array|JsonResponse
    {
        try {
            $donnees = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['message' => 'Le corps de la requête doit être un JSON valide.'], 400);
        }

        if (!is_array($donnees)) {
            return $this->json(['message' => 'Le corps de la requête doit être un objet JSON.'], 400);
        }

        return $donnees;
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function resoudreStructure(array $donnees, EntityManagerInterface $entityManager): Structure|JsonResponse
    {
        $structureId = $donnees['structure_id'] ?? null;

        if ($structureId === null) {
            $structure = $entityManager->getRepository(Structure::class)->findOneBy([], ['id' => 'ASC']);
        } elseif (is_int($structureId) || (is_string($structureId) && ctype_digit($structureId))) {
            $structure = $entityManager->getRepository(Structure::class)->find((int) $structureId);
        } else {
            return $this->json(['message' => 'L’identifiant de la structure est invalide.'], 400);
        }

        if ($structure === null) {
            return $this->json(['message' => 'Structure introuvable.'], 404);
        }

        return $structure;
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function resoudreDate(array $donnees): \DateTimeImmutable|JsonResponse
    {
        if (!isset($donnees['date_livraison']) || !is_string($donnees['date_livraison'])) {
            return $this->json(['message' => 'Le champ date_livraison (format YYYY-MM-DD) est requis.'], 400);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $donnees['date_livraison']);
        $erreurs = \DateTimeImmutable::getLastErrors();

        if ($date === false || ($erreurs !== false && ($erreurs['warning_count'] > 0 || $erreurs['error_count'] > 0))) {
            return $this->json(['message' => 'La date de livraison est invalide.'], 400);
        }

        return $date;
    }
}
