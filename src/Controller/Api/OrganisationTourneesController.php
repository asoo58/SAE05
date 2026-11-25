<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Service\Exception\ChauffeurIntrouvableException;
use App\Service\Exception\ChauffeursRequisException;
use App\Service\Exception\OrganisationConcurrenteException;
use App\Service\OrganisationTourneesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/structures')]
class OrganisationTourneesController extends AbstractController
{
    #[Route('/{id}/organiser-tournees', name: 'api_structures_organiser_tournees', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function organiser(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        OrganisationTourneesService $organisateur
    ): JsonResponse {
        $structure = $entityManager->getRepository(Structure::class)->find($id);
        if ($structure === null) {
            return $this->json(['message' => 'Structure introuvable.'], 404);
        }

        try {
            $donnees = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['message' => 'Le corps de la requête doit être un JSON valide.'], 400);
        }

        if (!is_array($donnees) || !isset($donnees['date_livraison']) || !is_string($donnees['date_livraison'])) {
            return $this->json(['message' => 'Le champ date_livraison (format YYYY-MM-DD) est requis.'], 400);
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $donnees['date_livraison']);
        $dateErreurs = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($dateErreurs !== false && ($dateErreurs['warning_count'] > 0 || $dateErreurs['error_count'] > 0))) {
            return $this->json(['message' => 'La date de livraison est invalide.'], 400);
        }

        $chauffeursIds = $donnees['chauffeurs'] ?? [];
        if (!is_array($chauffeursIds)) {
            $chauffeursIds = [];
        }

        try {
            $resultat = $organisateur->organiser($structure, $date, array_values($chauffeursIds));
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
}
