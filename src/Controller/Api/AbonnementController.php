<?php

namespace App\Controller\Api;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Produit;
use App\Entity\Calendrier;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/abonnements')]
#[OA\Tag(name: 'Abonnements', description: 'Gestion des abonnements aux paniers Cocagne')]
class AbonnementController extends AbstractController
{
    #[Route('', name: 'api_abonnements_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste complète des abonnements.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Abonnement::class, groups: ['api_read'])))
    )]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $abonnements = $em->getRepository(Abonnement::class)->findAll();
        return $this->json($abonnements, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_abonnements_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données pour créer un abonnement (nécessite les IDs des entités liées)',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-11-01'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2027-11-01'),
                new OA\Property(property: 'remisePourcentage', type: 'string', example: '5.50'),
                new OA\Property(property: 'statut', type: 'string', example: 'actif'),
                new OA\Property(property: 'adherent_id', type: 'integer', example: 1),
                new OA\Property(property: 'produit_id', type: 'integer', example: 1),
                new OA\Property(property: 'calendrier_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Abonnement créé avec succès.', content: new OA\JsonContent(ref: new Model(type: Abonnement::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides ou entité liée introuvable.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adherent_id'], $data['produit_id'], $data['calendrier_id'])) {
            return $this->json(['message' => 'Les IDs de l\'adhérent, du produit et du calendrier sont obligatoires.'], 400);
        }

        $abonnement = new Abonnement();
        if (isset($data['date_debut'])) $abonnement->setDateDebut(new \DateTime($data['date_debut']));
        if (isset($data['date_fin'])) $abonnement->setDateFin(new \DateTime($data['date_fin']));
        if (isset($data['remisePourcentage'])) $abonnement->setRemisePourcentage($data['remisePourcentage']);
        if (isset($data['statut'])) $abonnement->setStatut($data['statut']);

        // Récupération des relations
        $adherent = $em->getRepository(Adherent::class)->find($data['adherent_id']);
        $produit = $em->getRepository(Produit::class)->find($data['produit_id']);
        $calendrier = $em->getRepository(Calendrier::class)->find($data['calendrier_id']);

        if (!$adherent || !$produit || !$calendrier) {
            return $this->json(['message' => 'L\'une des entités liées (Adhérent, Produit, Calendrier) est introuvable avec l\'ID fourni.'], 400);
        }

        $abonnement->setAdherent($adherent);
        $abonnement->setProduit($produit);
        $abonnement->setCalendrier($calendrier);

        $em->persist($abonnement);
        $em->flush();

        return $this->json($abonnement, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_abonnements_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'abonnement à modifier', required: true)]
    #[OA\RequestBody(
        description: 'Modification du statut ou de la remise',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'statut', type: 'string', example: 'suspendu'),
                new OA\Property(property: 'remisePourcentage', type: 'string', example: '10.00')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Abonnement mis à jour.')]
    #[OA\Response(response: 404, description: 'Abonnement introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $abonnement = $em->getRepository(Abonnement::class)->find($id);
        if (!$abonnement) return $this->json(['message' => 'Abonnement non trouvé'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['statut'])) $abonnement->setStatut($data['statut']);
        if (isset($data['remisePourcentage'])) $abonnement->setRemisePourcentage($data['remisePourcentage']);
        
        $em->flush();
        return $this->json($abonnement, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_abonnements_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'abonnement à supprimer', required: true)]
    #[OA\Response(response: 204, description: 'Abonnement supprimé.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $abonnement = $em->getRepository(Abonnement::class)->find($id);
        if (!$abonnement) return $this->json(['message' => 'Abonnement non trouvé'], 404);

        $em->remove($abonnement);
        $em->flush();

        return $this->json(null, 204);
    }
}