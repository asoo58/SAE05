<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/chauffeurs')]
#[OA\Tag(name: 'Chauffeurs', description: 'Gestion des chauffeurs pour les tournées de livraison')]
class ChauffeurController extends AbstractController
{
    #[Route('', name: 'api_chauffeurs_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des chauffeurs.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Chauffeur::class, groups: ['api_read'])))
    )]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $chauffeurs = $em->getRepository(Chauffeur::class)->findAll();
        return $this->json($chauffeurs, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_chauffeurs_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données pour ajouter un chauffeur',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'numero_permis', type: 'string', example: '123456789ABC'),
                new OA\Property(property: 'date_validite_permis', type: 'string', format: 'date', example: '2030-12-31'),
                new OA\Property(property: 'date_fimo_fco', type: 'string', format: 'date', example: '2028-06-15')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Chauffeur créé avec succès.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $chauffeur = new Chauffeur();
        
        if (isset($data['numero_permis'])) $chauffeur->setNumeroPermis($data['numero_permis']);
        if (isset($data['date_validite_permis'])) $chauffeur->setDateValiditePermis(new \DateTime($data['date_validite_permis']));
        if (isset($data['date_fimo_fco'])) $chauffeur->setDateFimoFco(new \DateTime($data['date_fimo_fco']));

        $em->persist($chauffeur);
        $em->flush();

        return $this->json($chauffeur, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_chauffeurs_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du chauffeur', required: true)]
    #[OA\RequestBody(
        description: 'Mise à jour des informations du chauffeur',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'numero_permis', type: 'string', example: '987654321ZYX'),
                new OA\Property(property: 'date_validite_permis', type: 'string', format: 'date', example: '2032-01-01')
            ]
        )
    )]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $chauffeur = $em->getRepository(Chauffeur::class)->find($id);
        if (!$chauffeur) return $this->json(['message' => 'Chauffeur non trouvé'], 404);

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['numero_permis'])) $chauffeur->setNumeroPermis($data['numero_permis']);
        if (isset($data['date_validite_permis'])) $chauffeur->setDateValiditePermis(new \DateTime($data['date_validite_permis']));
        if (isset($data['date_fimo_fco'])) $chauffeur->setDateFimoFco(new \DateTime($data['date_fimo_fco']));

        $em->flush();
        return $this->json($chauffeur, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_chauffeurs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $chauffeur = $em->getRepository(Chauffeur::class)->find($id);
        if (!$chauffeur) return $this->json(['message' => 'Chauffeur non trouvé'], 404);

        $em->remove($chauffeur);
        $em->flush();

        return $this->json(null, 204);
    }
}