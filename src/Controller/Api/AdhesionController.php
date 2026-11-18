<?php

namespace App\Controller\Api;

use App\Entity\Adhesion;
use App\Entity\Adherent;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/adhesions')]
#[OA\Tag(name: 'Adhésions', description: 'Gestion du règlement des cotisations annuelles')]
class AdhesionController extends AbstractController
{
    #[Route('', name: 'api_adhesions_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Liste des adhésions.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Adhesion::class, groups: ['api_read'])))
    )]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $adhesions = $em->getRepository(Adhesion::class)->findAll();
        return $this->json($adhesions, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_adhesions_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Création d\'une adhésion',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date_reglement', type: 'string', format: 'date', example: '2026-11-15'),
                new OA\Property(property: 'est_a_jour', type: 'boolean', example: true),
                new OA\Property(property: 'adherent_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Adhésion enregistrée.')]
    #[OA\Response(response: 400, description: 'Données invalides ou Adhérent introuvable.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adherent_id'])) {
            return $this->json(['message' => 'L\'ID de l\'adhérent est obligatoire.'], 400);
        }

        $adherent = $em->getRepository(Adherent::class)->find($data['adherent_id']);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable.'], 400);
        }

        $adhesion = new Adhesion();
        if (isset($data['date_reglement'])) $adhesion->setDateReglement(new \DateTime($data['date_reglement']));
        if (isset($data['est_a_jour'])) $adhesion->setEstAJour($data['est_a_jour']);
        
        $adhesion->setAdherent($adherent);

        $em->persist($adhesion);
        $em->flush();

        return $this->json($adhesion, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adhesions_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhésion', required: true)]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'est_a_jour', type: 'boolean', example: false)
            ]
        )
    )]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $adhesion = $em->getRepository(Adhesion::class)->find($id);
        if (!$adhesion) return $this->json(['message' => 'Adhésion non trouvée'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['est_a_jour'])) $adhesion->setEstAJour($data['est_a_jour']);
        
        $em->flush();
        return $this->json($adhesion, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adhesions_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adhesion = $em->getRepository(Adhesion::class)->find($id);
        if (!$adhesion) return $this->json(['message' => 'Adhésion non trouvée'], 404);

        $em->remove($adhesion);
        $em->flush();
        return $this->json(null, 204);
    }
}