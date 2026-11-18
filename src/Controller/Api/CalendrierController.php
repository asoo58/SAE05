<?php

namespace App\Controller\Api;

use App\Entity\Calendrier;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/calendriers')]
#[OA\Tag(name: 'Calendriers', description: 'Gestion des calendriers de livraison')]
class CalendrierController extends AbstractController
{
    #[Route('', name: 'api_calendriers_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des calendriers.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Calendrier::class, groups: ['api_read'])))
    )]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $calendriers = $em->getRepository(Calendrier::class)->findAll();
        return $this->json($calendriers, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_calendriers_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données du calendrier à créer',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', example: 'Planning Mercredi'),
                new OA\Property(property: 'frequence', type: 'string', example: 'hebdomadaire')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Calendrier créé.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['frequence'])) {
            return $this->json(['message' => 'La fréquence est obligatoire.'], 400);
        }

        $calendrier = new Calendrier();
        if (isset($data['nom'])) $calendrier->setNom($data['nom']);
        $calendrier->setFrequence($data['frequence']);

        $em->persist($calendrier);
        $em->flush();

        return $this->json($calendrier, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendriers_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du calendrier', required: true)]
    #[OA\RequestBody(
        description: 'Modification du calendrier',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', example: 'Planning Jeudi'),
                new OA\Property(property: 'frequence', type: 'string', example: 'mensuel')
            ]
        )
    )]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $calendrier = $em->getRepository(Calendrier::class)->find($id);
        if (!$calendrier) return $this->json(['message' => 'Calendrier introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) $calendrier->setNom($data['nom']);
        if (isset($data['frequence'])) $calendrier->setFrequence($data['frequence']);
        
        $em->flush();
        return $this->json($calendrier, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendriers_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $calendrier = $em->getRepository(Calendrier::class)->find($id);
        if (!$calendrier) return $this->json(['message' => 'Calendrier introuvable'], 404);

        $em->remove($calendrier);
        $em->flush();
        return $this->json(null, 204);
    }
}