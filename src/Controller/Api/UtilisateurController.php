<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/utilisateurs')]
#[OA\Tag(name: 'Utilisateurs', description: 'Points de terminaison pour la gestion des comptes utilisateurs')]
class UtilisateurController extends AbstractController
{
    #[Route('', name: 'api_utilisateurs_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste complète des utilisateurs.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
        )
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();
        return $this->json($utilisateurs, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_utilisateurs_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données requises pour la création d\'un nouvel utilisateur',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'etudiant@cocagne.fr'),
                new OA\Property(property: 'password', type: 'string', example: 'MotDePasseSecret123!'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER'))
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'L\'utilisateur a été créé avec succès.',
        content: new OA\JsonContent(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 400, description: 'Erreur de syntaxe JSON ou données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé. Privilèges administrateur (ROLE_ADMIN) requis.')]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse
    {
        try {
            $utilisateur = $serializer->deserialize($request->getContent(), Utilisateur::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['message' => 'Format JSON invalide'], 400);
        }

        $errors = $validator->validate($utilisateur);
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }

        if ($utilisateur->getPassword()) {
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));
        }

        if (empty($utilisateur->getRoles())) {
            $utilisateur->setRoles(['ROLE_USER']);
        }

        $em->persist($utilisateur);
        $em->flush();

        return $this->json($utilisateur, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_utilisateurs_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'utilisateur à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs à mettre à jour (partiels acceptés)',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'nouveau.mail@cocagne.fr'),
                new OA\Property(property: 'password', type: 'string', example: 'NouveauMdp456!'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_ADMIN'))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'L\'utilisateur a été modifié avec succès.',
        content: new OA\JsonContent(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 400, description: 'Erreur de syntaxe JSON.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé. Privilèges administrateur (ROLE_ADMIN) requis.')]
    #[OA\Response(response: 404, description: 'L\'utilisateur demandé est introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Format JSON invalide'], 400);
        }

        if (isset($data['email'])) { $utilisateur->setEmail($data['email']); }
        if (isset($data['roles']) && is_array($data['roles'])) { $utilisateur->setRoles($data['roles']); }
        if (isset($data['password'])) {
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $data['password']));
        }

        $em->flush();

        return $this->json($utilisateur, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_utilisateurs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'utilisateur à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Suppression réussie. Le serveur ne renvoie aucun contenu.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé. Privilèges administrateur (ROLE_ADMIN) requis.')]
    #[OA\Response(response: 404, description: 'L\'utilisateur demandé est introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $em->remove($utilisateur);
        $em->flush();

        return $this->json(null, 204);
    }
}