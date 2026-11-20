<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/chauffeurs')]
#[OA\Tag(name: 'Chauffeurs', description: 'Gestion des chauffeurs pour les tournées de livraison')]
class ChauffeurController extends AbstractController
{
    #[Route('', name: 'api_chauffeurs_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Liste des chauffeurs')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des chauffeurs.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Chauffeur::class, groups: ['api_read'])))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $chauffeurs = $em->getRepository(Chauffeur::class)->findAll();
        return $this->json($chauffeurs, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_chauffeurs_get_one', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Détail d\'un chauffeur')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique du chauffeur', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Détail du chauffeur retourné.',
        content: new OA\JsonContent(ref: new Model(type: Chauffeur::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'Chauffeur introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $chauffeur = $em->getRepository(Chauffeur::class)->find($id);

        if (!$chauffeur) {
            return $this->json(['error' => 'Chauffeur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($chauffeur, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_chauffeurs_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Créer un chauffeur')]
    #[OA\RequestBody(
        description: 'Données pour créer un chauffeur',
        required: true,
        content: new OA\JsonContent(
            required: ['numero_permis'],
            properties: [
                new OA\Property(property: 'numero_permis', type: 'string', maxLength: 30, example: '123456789ABC'),
                new OA\Property(property: 'date_validite_permis', type: 'string', format: 'date', nullable: true, example: '2030-12-31'),
                new OA\Property(property: 'date_fimo_fco', type: 'string', format: 'date', nullable: true, example: '2028-06-15')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Chauffeur créé avec succès.', content: new OA\JsonContent(ref: new Model(type: Chauffeur::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['numero_permis'])) {
            return $this->json(['error' => 'Le numéro de permis est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $chauffeur = new Chauffeur();
        $chauffeur->setNumeroPermis($data['numero_permis']);

        if (!empty($data['date_validite_permis'])) {
            try {
                $chauffeur->setDateValiditePermis(new \DateTime($data['date_validite_permis']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_validite_permis.'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['date_fimo_fco'])) {
            try {
                $chauffeur->setDateFimoFco(new \DateTime($data['date_fimo_fco']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_fimo_fco.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $em->persist($chauffeur);
        $em->flush();

        return $this->json($chauffeur, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_chauffeurs_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifier un chauffeur')]
    #[OA\Patch(summary: 'Modifier partiellement un chauffeur')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du chauffeur à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du chauffeur à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'numero_permis', type: 'string', maxLength: 30, example: '987654321ZYX'),
                new OA\Property(property: 'date_validite_permis', type: 'string', format: 'date', nullable: true, example: '2032-01-01'),
                new OA\Property(property: 'date_fimo_fco', type: 'string', format: 'date', nullable: true, example: '2030-06-15')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Chauffeur mis à jour avec succès.', content: new OA\JsonContent(ref: new Model(type: Chauffeur::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Chauffeur introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $chauffeur = $em->getRepository(Chauffeur::class)->find($id);
        if (!$chauffeur) {
            return $this->json(['error' => 'Chauffeur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('numero_permis', $data)) {
            $chauffeur->setNumeroPermis($data['numero_permis']);
        }
        if (array_key_exists('date_validite_permis', $data)) {
            if ($data['date_validite_permis'] === null) {
                $chauffeur->setDateValiditePermis(null);
            } else {
                try {
                    $chauffeur->setDateValiditePermis(new \DateTime($data['date_validite_permis']));
                } catch (\Exception) {
                    return $this->json(['error' => 'Format de date invalide pour date_validite_permis.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }
        if (array_key_exists('date_fimo_fco', $data)) {
            if ($data['date_fimo_fco'] === null) {
                $chauffeur->setDateFimoFco(null);
            } else {
                try {
                    $chauffeur->setDateFimoFco(new \DateTime($data['date_fimo_fco']));
                } catch (\Exception) {
                    return $this->json(['error' => 'Format de date invalide pour date_fimo_fco.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $em->flush();
        return $this->json($chauffeur, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_chauffeurs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Supprimer un chauffeur')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du chauffeur à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Chauffeur supprimé avec succès.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Chauffeur introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $chauffeur = $em->getRepository(Chauffeur::class)->find($id);
        if (!$chauffeur) {
            return $this->json(['error' => 'Chauffeur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($chauffeur);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
