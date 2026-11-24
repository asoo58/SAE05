<?php

namespace App\Controller;

use App\Entity\Depot;
use App\Entity\Structure;
use App\Entity\Tournee;
use App\Service\ItineraireTourneesService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Module de visualisation et de réorganisation des tournées.
 *
 * Comme le reste de l'outil, la page est accessible sans authentification.
 * Toutes les modifications passent par des formulaires POST suivis d'une
 * redirection (pattern POST/Redirect/GET).
 */
final class ItineraireTourneesController extends AbstractController
{
    private const JOURS = [
        'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche',
    ];

    #[Route('/tournees/itineraire', name: 'app_tournees_itineraire', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraireTourneesService $itineraire
    ): Response {
        $structureId = $request->query->get('structure');
        $structure = null;

        if (is_string($structureId) && ctype_digit($structureId)) {
            $structure = $entityManager->getRepository(Structure::class)->find((int) $structureId);
        }
        if ($structure === null) {
            $structureId = null;
        }

        $repository = $entityManager->getRepository(Tournee::class);
        $tourneesEntites = $repository->findAvecEtapes($structure);

        $tournees = [];
        /** @var array<int, array{id:int, code:string, structure:int}> $cibles */
        $cibles = [];

        foreach ($tourneesEntites as $tournee) {
            $etapes = [];

            foreach ($itineraire->etapesOrdonnees($tournee) as $etape) {
                $depot = $etape->getDepot();
                if ($depot === null) {
                    continue;
                }

                $etapes[] = [
                    'depot_id' => $depot->getId(),
                    'ordre' => $etape->getOrdrePassage(),
                    'nom' => $depot->getNom(),
                    'ville' => $depot->getVille(),
                    'adresse' => $depot->getAdresse(),
                    'code_postal' => $depot->getCodePostal(),
                    'capacite' => $depot->getCapaciteMaxPaniers(),
                    'referent' => $depot->getReferentNom(),
                    'referent_tel' => $depot->getReferentTel(),
                ];
            }

            $structureTournee = $tournee->getStructure();

            $tournees[] = [
                'id' => $tournee->getId(),
                'code' => $tournee->getCodeTournee(),
                'nom' => $tournee->getNom(),
                'couleur' => $tournee->getCouleur() ?? '#2E7D32',
                'jour_preparation' => $tournee->getJourPreparation(),
                'jour_livraison' => $tournee->getJourLivraison(),
                'structure_id' => $structureTournee?->getId(),
                'structure' => $structureTournee?->getNomCommercial(),
                'etapes' => $etapes,
            ];

            $cibles[] = [
                'id' => (int) $tournee->getId(),
                'code' => (string) $tournee->getCodeTournee(),
                'structure' => (int) $structureTournee?->getId(),
            ];
        }

        // Tournées cibles disponibles par structure (pour le changement de tournée).
        $ciblesParStructure = [];
        foreach ($cibles as $cible) {
            $ciblesParStructure[$cible['structure']][] = $cible;
        }

        // Dépôts disponibles par structure (pour l'ajout à une tournée).
        $depotsParStructure = [];
        foreach ($entityManager->getRepository(Depot::class)->findBy([], ['nom' => 'ASC']) as $depot) {
            $depotsParStructure[(int) $depot->getStructure()?->getId()][] = [
                'id' => $depot->getId(),
                'nom' => $depot->getNom(),
                'ville' => $depot->getVille(),
            ];
        }

        return $this->render('tournees/itineraire.html.twig', [
            'structures' => $entityManager->getRepository(Structure::class)->findBy([], ['id' => 'ASC']),
            'tournees' => $tournees,
            'cibles_par_structure' => $ciblesParStructure,
            'depots_par_structure' => $depotsParStructure,
            'jours' => self::JOURS,
            'filtres' => ['structure' => $structureId],
        ]);
    }

    #[Route('/tournees/itineraire/{id}/deplacer', name: 'app_tournees_itineraire_deplacer', methods: ['POST'])]
    public function deplacer(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraireTourneesService $itineraire
    ): Response {
        $tournee = $entityManager->getRepository(Tournee::class)->find($id);
        if ($tournee === null) {
            throw $this->createNotFoundException('Tournée introuvable.');
        }

        $depot = $this->resoudreDepot($request, $entityManager);
        $direction = $request->request->get('direction');

        if ($depot !== null && in_array($direction, ['haut', 'bas'], true)) {
            if ($itineraire->deplacer($tournee, $depot, $direction)) {
                $this->addFlash('success', sprintf(
                    '« %s » a été déplacé dans la tournée %s.',
                    $depot->getNom(),
                    $tournee->getCodeTournee()
                ));
            } else {
                $this->addFlash('warning', 'Ce dépôt ne peut pas être déplacé dans cette direction.');
            }
        }

        return $this->redirectAvecFiltre($request);
    }

    #[Route('/tournees/itineraire/{id}/transferer', name: 'app_tournees_itineraire_transferer', methods: ['POST'])]
    public function transferer(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraireTourneesService $itineraire
    ): Response {
        $source = $entityManager->getRepository(Tournee::class)->find($id);
        if ($source === null) {
            throw $this->createNotFoundException('Tournée introuvable.');
        }

        $depot = $this->resoudreDepot($request, $entityManager);
        $cibleId = $request->request->get('cible');

        if ($depot === null || !is_string($cibleId) || !ctype_digit($cibleId)) {
            $this->addFlash('warning', 'Déplacement impossible : dépôt ou tournée cible invalide.');

            return $this->redirectAvecFiltre($request);
        }

        $cible = $entityManager->getRepository(Tournee::class)->find((int) $cibleId);

        if ($cible === null || $cible->getId() === $source->getId()) {
            $this->addFlash('warning', 'Choisis une autre tournée que la tournée actuelle.');

            return $this->redirectAvecFiltre($request);
        }

        if ($cible->getStructure()?->getId() !== $depot->getStructure()?->getId()) {
            $this->addFlash('warning', 'La tournée cible doit appartenir à la même structure que le dépôt.');

            return $this->redirectAvecFiltre($request);
        }

        if ($itineraire->transferer($source, $depot, $cible)) {
            $this->addFlash('success', sprintf(
                '« %s » a été déplacé de la tournée %s vers la tournée %s.',
                $depot->getNom(),
                $source->getCodeTournee(),
                $cible->getCodeTournee()
            ));
        } else {
            $this->addFlash('warning', 'Ce dépôt n’appartient pas à cette tournée.');
        }

        return $this->redirectAvecFiltre($request);
    }

    #[Route('/tournees/itineraire/{id}/ajouter', name: 'app_tournees_itineraire_ajouter', methods: ['POST'])]
    public function ajouter(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraireTourneesService $itineraire
    ): Response {
        $tournee = $entityManager->getRepository(Tournee::class)->find($id);
        if ($tournee === null) {
            throw $this->createNotFoundException('Tournée introuvable.');
        }

        $depot = $this->resoudreDepot($request, $entityManager);
        if ($depot === null) {
            $this->addFlash('warning', 'Choisis un dépôt à ajouter.');

            return $this->redirectAvecFiltre($request);
        }

        if ($depot->getStructure()?->getId() !== $tournee->getStructure()?->getId()) {
            $this->addFlash('warning', 'Le dépôt et la tournée doivent appartenir à la même structure.');

            return $this->redirectAvecFiltre($request);
        }

        if ($itineraire->ajouter($tournee, $depot)) {
            $this->addFlash('success', sprintf(
                '« %s » a été ajouté à la tournée %s.',
                $depot->getNom(),
                $tournee->getCodeTournee()
            ));
        } else {
            $this->addFlash('warning', 'Ce dépôt figure déjà dans cette tournée.');
        }

        return $this->redirectAvecFiltre($request);
    }

    #[Route('/tournees/itineraire/{id}/retirer', name: 'app_tournees_itineraire_retirer', methods: ['POST'])]
    public function retirer(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ItineraireTourneesService $itineraire
    ): Response {
        $tournee = $entityManager->getRepository(Tournee::class)->find($id);
        if ($tournee === null) {
            throw $this->createNotFoundException('Tournée introuvable.');
        }

        $depot = $this->resoudreDepot($request, $entityManager);

        if ($depot !== null && $itineraire->retirer($tournee, $depot)) {
            $this->addFlash('success', sprintf(
                '« %s » a été retiré de la tournée %s.',
                $depot->getNom(),
                $tournee->getCodeTournee()
            ));
        } else {
            $this->addFlash('warning', 'Ce dépôt n’appartient pas à cette tournée.');
        }

        return $this->redirectAvecFiltre($request);
    }

    #[Route('/tournees/itineraire/{id}/modifier', name: 'app_tournees_itineraire_modifier', methods: ['POST'])]
    public function modifier(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $tournee = $entityManager->getRepository(Tournee::class)->find($id);
        if ($tournee === null) {
            throw $this->createNotFoundException('Tournée introuvable.');
        }

        $code = trim((string) $request->request->get('code_tournee', ''));
        $nom = trim((string) $request->request->get('nom', ''));
        $jourPreparation = (string) $request->request->get('jour_preparation', '');
        $jourLivraison = (string) $request->request->get('jour_livraison', '');
        $couleur = (string) $request->request->get('couleur', '');

        if ($code === '' || mb_strlen($code) > 20) {
            $this->addFlash('warning', 'L’identifiant de tournée est obligatoire (20 caractères maximum).');

            return $this->redirectAvecFiltre($request);
        }
        if (mb_strlen($nom) > 100) {
            $this->addFlash('warning', 'Le nom de la tournée est limité à 100 caractères.');

            return $this->redirectAvecFiltre($request);
        }
        if ($jourPreparation !== '' && !in_array($jourPreparation, self::JOURS, true)) {
            $this->addFlash('warning', 'Le jour de préparation est invalide.');

            return $this->redirectAvecFiltre($request);
        }
        if ($jourLivraison !== '' && !in_array($jourLivraison, self::JOURS, true)) {
            $this->addFlash('warning', 'Le jour de livraison est invalide.');

            return $this->redirectAvecFiltre($request);
        }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $couleur)) {
            $this->addFlash('warning', 'La couleur doit être au format #RRGGBB.');

            return $this->redirectAvecFiltre($request);
        }

        $tournee
            ->setCodeTournee($code)
            ->setNom($nom !== '' ? $nom : null)
            ->setJourPreparation($jourPreparation !== '' ? $jourPreparation : null)
            ->setJourLivraison($jourLivraison !== '' ? $jourLivraison : null)
            ->setCouleur($couleur);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('warning', 'Cet identifiant de tournée est déjà utilisé dans cette structure.');

            return $this->redirectAvecFiltre($request);
        }

        $this->addFlash('success', sprintf('La tournée %s a été mise à jour.', $code));

        return $this->redirectAvecFiltre($request);
    }

    private function resoudreDepot(Request $request, EntityManagerInterface $entityManager): ?Depot
    {
        $depotId = $request->request->get('depot');

        if (!is_string($depotId) || !ctype_digit($depotId)) {
            return null;
        }

        return $entityManager->getRepository(Depot::class)->find((int) $depotId);
    }

    private function redirectAvecFiltre(Request $request): Response
    {
        $parametres = [];
        $structure = $request->request->get('structure');

        if (is_string($structure) && ctype_digit($structure)) {
            $parametres['structure'] = (int) $structure;
        }

        return $this->redirectToRoute('app_tournees_itineraire', $parametres);
    }
}
