<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    #[OA\Parameter(
        name: 'id_structure',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de structure',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste complète des utilisateurs.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
        )
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $criteres = [];
        if ($request->query->has('id_structure') && $request->query->get('id_structure') !== '') {
            $criteres['structure'] = (int) $request->query->get('id_structure');
        }

        if (!empty($criteres)) {
            $utilisateurs = $em->getRepository(Utilisateur::class)->findBy($criteres, ['nom' => 'ASC', 'prenom' => 'ASC']);
        } else {
            $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();
        }

        return $this->json($utilisateurs, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_utilisateurs_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'Identifiant unique de l\'utilisateur',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Détail de l\'utilisateur.',
        content: new OA\JsonContent(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'L\'utilisateur demandé est introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($utilisateur, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_utilisateurs_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données requises pour la création d\'un nouvel utilisateur',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'etudiant@cocagne.fr'),
                new OA\Property(property: 'password', type: 'string', example: 'MotDePasseSecret123!'),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Dupont'),
                new OA\Property(property: 'prenom', type: 'string', maxLength: 100, example: 'Jean'),
                new OA\Property(property: 'telephone', type: 'string', maxLength: 20, nullable: true, example: '0601020304'),
                new OA\Property(property: 'actif', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure', type: 'integer', nullable: true, example: 1),
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
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $utilisateur = $serializer->deserialize($request->getContent(), Utilisateur::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom'])) {
            $utilisateur->setNom($data['nom']);
        } elseif ($utilisateur->getNom() === null) {
            $utilisateur->setNom('Utilisateur');
        }

        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        } elseif ($utilisateur->getPrenom() === null) {
            $utilisateur->setPrenom('Nouveau');
        }

        if (isset($data['telephone'])) {
            $utilisateur->setTelephone($data['telephone']);
        }

        if (isset($data['actif'])) {
            $utilisateur->setActif((bool) $data['actif']);
        } elseif ($utilisateur->isActif() === null) {
            $utilisateur->setActif(true);
        }

        if (!empty($data['id_structure'])) {
            $structure = $em->getRepository(Structure::class)->find((int) $data['id_structure']);
            if (!$structure) {
                return $this->json(['message' => 'Structure introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $utilisateur->setStructure($structure);
        } elseif ($utilisateur->getStructure() === null) {
            $structureParDefaut = $em->getRepository(Structure::class)->findOneBy([]);
            if ($structureParDefaut) {
                $utilisateur->setStructure($structureParDefaut);
            }
        }

        $errors = $validator->validate($utilisateur);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($utilisateur->getPassword()) {
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));
        }

        if (empty($utilisateur->getRoles())) {
            $utilisateur->setRoles(['ROLE_USER']);
        }

        $em->persist($utilisateur);
        $em->flush();

        return $this->json($utilisateur, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_utilisateurs_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'Identifiant unique de l\'utilisateur à modifier',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        description: 'Champs à mettre à jour (partiels acceptés)',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'nouveau.mail@cocagne.fr'),
                new OA\Property(property: 'password', type: 'string', example: 'NouveauMdp456!'),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Dupont'),
                new OA\Property(property: 'prenom', type: 'string', maxLength: 100, example: 'Jean'),
                new OA\Property(property: 'telephone', type: 'string', maxLength: 20, nullable: true, example: '0601020304'),
                new OA\Property(property: 'actif', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_ADMIN'))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'L\'utilisateur a été modifié avec succès.',
        content: new OA\JsonContent(ref: new Model(type: Utilisateur::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 400, description: 'Erreur de syntaxe JSON ou données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé. Privilèges administrateur (ROLE_ADMIN) requis.')]
    #[OA\Response(response: 404, description: 'L\'utilisateur demandé est introuvable.')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $utilisateur = $em->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['email'])) {
            $utilisateur->setEmail($data['email']);
        }
        if (isset($data['nom'])) {
            $utilisateur->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }
        if (isset($data['telephone'])) {
            $utilisateur->setTelephone($data['telephone']);
        }
        if (isset($data['actif'])) {
            $utilisateur->setActif((bool) $data['actif']);
        }
        if (!empty($data['id_structure'])) {
            $structure = $em->getRepository(Structure::class)->find((int) $data['id_structure']);
            if (!$structure) {
                return $this->json(['message' => 'Structure introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $utilisateur->setStructure($structure);
        }
        if (isset($data['roles']) && is_array($data['roles'])) {
            $utilisateur->setRoles($data['roles']);
        }
        if (isset($data['password'])) {
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $data['password']));
        }

        $em->flush();

        return $this->json($utilisateur, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_utilisateurs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'Identifiant unique de l\'utilisateur à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 204, description: 'Suppression réussie. Le serveur ne renvoie aucun contenu.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé. Privilèges administrateur (ROLE_ADMIN) requis.')]
    #[OA\Response(response: 404, description: 'L\'utilisateur demandé est introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($utilisateur);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
