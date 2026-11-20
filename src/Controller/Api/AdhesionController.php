<?php

namespace App\Controller\Api;

use App\Entity\Adherent;
use App\Entity\Adhesion;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/adhesions')]
#[OA\Tag(name: 'Adhésions', description: 'Gestion des adhésions et cotisations annuelles')]
class AdhesionController extends AbstractController
{
    #[Route('', name: 'api_adhesions_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Liste des adhésions')]
    #[OA\Parameter(
        name: 'adherent_id',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant d\'adhérent',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des adhésions.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Adhesion::class, groups: ['api_read'])))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $criteres = [];
        if ($request->query->has('adherent_id') && $request->query->get('adherent_id') !== '') {
            $criteres['adherent'] = (int) $request->query->get('adherent_id');
        }

        $adhesions = !empty($criteres)
            ? $em->getRepository(Adhesion::class)->findBy($criteres, ['id' => 'ASC'])
            : $em->getRepository(Adhesion::class)->findAll();

        return $this->json($adhesions, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adhesions_get_one', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Détail d\'une adhésion')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'adhésion', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Détail de l\'adhésion.',
        content: new OA\JsonContent(ref: new Model(type: Adhesion::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'Adhésion introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adhesion = $em->getRepository(Adhesion::class)->find($id);

        if (!$adhesion) {
            return $this->json(['error' => 'Adhésion introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($adhesion, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_adhesions_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Créer une adhésion')]
    #[OA\RequestBody(
        description: 'Données requises pour créer une adhésion',
        required: true,
        content: new OA\JsonContent(
            required: ['adherent_id'],
            properties: [
                new OA\Property(property: 'adherent_id', type: 'integer', example: 1),
                new OA\Property(property: 'date_reglement', type: 'string', format: 'date', nullable: true, example: '2026-11-15'),
                new OA\Property(property: 'est_a_jour', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Adhésion enregistrée avec succès.', content: new OA\JsonContent(ref: new Model(type: Adhesion::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides ou Adhérent introuvable.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['adherent_id'])) {
            return $this->json(['error' => 'L\'ID de l\'adhérent est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = $em->getRepository(Adherent::class)->find((int) $data['adherent_id']);
        if (!$adherent) {
            return $this->json(['error' => 'Adhérent introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $adhesion = new Adhesion();
        $adhesion->setAdherent($adherent);

        if (!empty($data['date_reglement'])) {
            try {
                $adhesion->setDateReglement(new \DateTime($data['date_reglement']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_reglement.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['est_a_jour'])) {
            $adhesion->setEstAJour((bool) $data['est_a_jour']);
        }

        $em->persist($adhesion);
        $em->flush();

        return $this->json($adhesion, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adhesions_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifier une adhésion')]
    #[OA\Patch(summary: 'Modifier partiellement une adhésion')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhésion à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs à mettre à jour sur l\'adhésion',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'est_a_jour', type: 'boolean', example: true),
                new OA\Property(property: 'date_reglement', type: 'string', format: 'date', nullable: true, example: '2026-11-20'),
                new OA\Property(property: 'adherent_id', type: 'integer', nullable: true, example: 1)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Adhésion mise à jour avec succès.', content: new OA\JsonContent(ref: new Model(type: Adhesion::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Adhésion introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $adhesion = $em->getRepository(Adhesion::class)->find($id);
        if (!$adhesion) {
            return $this->json(['error' => 'Adhésion non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('est_a_jour', $data)) {
            $adhesion->setEstAJour((bool) $data['est_a_jour']);
        }
        if (array_key_exists('date_reglement', $data)) {
            if ($data['date_reglement'] === null) {
                $adhesion->setDateReglement(null);
            } else {
                try {
                    $adhesion->setDateReglement(new \DateTime($data['date_reglement']));
                } catch (\Exception) {
                    return $this->json(['error' => 'Format de date invalide pour date_reglement.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }
        if (!empty($data['adherent_id'])) {
            $adherent = $em->getRepository(Adherent::class)->find((int) $data['adherent_id']);
            if (!$adherent) {
                return $this->json(['error' => 'Adhérent introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $adhesion->setAdherent($adherent);
        }

        $em->flush();
        return $this->json($adhesion, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adhesions_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Supprimer une adhésion')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhésion à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Adhésion supprimée avec succès.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Adhésion introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adhesion = $em->getRepository(Adhesion::class)->find($id);
        if (!$adhesion) {
            return $this->json(['error' => 'Adhésion non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($adhesion);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
