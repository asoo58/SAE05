<?php

namespace App\Controller\Api;

use App\Entity\Calendrier;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/calendriers')]
#[OA\Tag(name: 'Calendriers', description: 'Gestion des calendriers de livraison')]
class CalendrierController extends AbstractController
{
    #[Route('', name: 'api_calendriers_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Liste des calendriers')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des calendriers.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Calendrier::class, groups: ['api_read'])))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $calendriers = $em->getRepository(Calendrier::class)->findAll();
        return $this->json($calendriers, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendriers_get_one', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Détail d\'un calendrier')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du calendrier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Détail du calendrier retourné.',
        content: new OA\JsonContent(ref: new Model(type: Calendrier::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'Calendrier introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $calendrier = $em->getRepository(Calendrier::class)->find($id);

        if (!$calendrier) {
            return $this->json(['error' => 'Calendrier introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($calendrier, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_calendriers_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Créer un calendrier')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un calendrier',
        required: true,
        content: new OA\JsonContent(
            required: ['frequence'],
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Planning Mercredi'),
                new OA\Property(property: 'frequence', type: 'string', maxLength: 50, example: 'hebdomadaire')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Calendrier créé avec succès.', content: new OA\JsonContent(ref: new Model(type: Calendrier::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides ou fréquence manquante.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['frequence']) || empty(trim($data['frequence']))) {
            return $this->json(['error' => 'La fréquence est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $calendrier = new Calendrier();
        if (isset($data['nom'])) {
            $calendrier->setNom($data['nom']);
        }
        $calendrier->setFrequence(trim($data['frequence']));

        $em->persist($calendrier);
        $em->flush();

        return $this->json($calendrier, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendriers_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifier un calendrier')]
    #[OA\Patch(summary: 'Modifier partiellement un calendrier')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du calendrier à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du calendrier à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Planning Jeudi'),
                new OA\Property(property: 'frequence', type: 'string', maxLength: 50, example: 'mensuel')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Calendrier mis à jour avec succès.', content: new OA\JsonContent(ref: new Model(type: Calendrier::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Calendrier introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $calendrier = $em->getRepository(Calendrier::class)->find($id);
        if (!$calendrier) {
            return $this->json(['error' => 'Calendrier introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nom', $data)) {
            $calendrier->setNom($data['nom']);
        }
        if (array_key_exists('frequence', $data)) {
            if (empty(trim($data['frequence']))) {
                return $this->json(['error' => 'La fréquence ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $calendrier->setFrequence(trim($data['frequence']));
        }

        $em->flush();
        return $this->json($calendrier, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_calendriers_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Supprimer un calendrier')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du calendrier à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Calendrier supprimé avec succès.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Calendrier introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $calendrier = $em->getRepository(Calendrier::class)->find($id);
        if (!$calendrier) {
            return $this->json(['error' => 'Calendrier introuvable'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($calendrier);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
