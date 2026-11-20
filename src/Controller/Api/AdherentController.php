<?php

namespace App\Controller\Api;

use App\Entity\Adherent;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/adherents')]
#[OA\Tag(name: 'Adhérents', description: 'Gestion des adhérents et membres de l\'association')]
class AdherentController extends AbstractController
{
    #[Route('', name: 'api_adherents_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Liste des adhérents')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des adhérents.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Adherent::class, groups: ['api_read'])))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $adherents = $em->getRepository(Adherent::class)->findAll();
        return $this->json($adherents, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adherents_get_one', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(summary: 'Détail d\'un adhérent')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'adhérent', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Détail de l\'adhérent retourné.',
        content: new OA\JsonContent(ref: new Model(type: Adherent::class, groups: ['api_read']))
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 404, description: 'Adhérent introuvable.')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adherent = $em->getRepository(Adherent::class)->find($id);

        if (!$adherent) {
            return $this->json(['error' => 'Adhérent introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($adherent, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_adherents_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(summary: 'Créer un adhérent')]
    #[OA\RequestBody(
        description: 'Données pour créer un nouvel adhérent',
        required: true,
        content: new OA\JsonContent(
            required: ['civilite', 'adresse', 'code_postal', 'ville'],
            properties: [
                new OA\Property(property: 'civilite', type: 'string', maxLength: 10, example: 'Mme'),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, nullable: true, example: 'Martin'),
                new OA\Property(property: 'prenom', type: 'string', maxLength: 100, nullable: true, example: 'Sophie'),
                new OA\Property(property: 'raison_social', type: 'string', maxLength: 150, nullable: true, example: null),
                new OA\Property(property: 'adresse', type: 'string', maxLength: 255, example: '15 Rue des Jardins'),
                new OA\Property(property: 'code_postal', type: 'string', maxLength: 10, example: '88100'),
                new OA\Property(property: 'ville', type: 'string', maxLength: 150, example: 'Saint-Dié-des-Vosges'),
                new OA\Property(property: 'telephone_secondaire', type: 'string', maxLength: 20, nullable: true, example: '0611223344'),
                new OA\Property(property: 'date_naissance', type: 'string', format: 'date', nullable: true, example: '1985-04-12'),
                new OA\Property(property: 'profession', type: 'string', maxLength: 100, nullable: true, example: 'Enseignante'),
                new OA\Property(property: 'optin_sms', type: 'boolean', example: true),
                new OA\Property(property: 'optin_email', type: 'boolean', example: true),
                new OA\Property(property: 'est_professionnel', type: 'boolean', example: false),
                new OA\Property(property: 'dispense_adhesion', type: 'boolean', example: false),
                new OA\Property(property: 'id_utilisateur', type: 'integer', nullable: true, example: 1)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Adhérent créé avec succès.', content: new OA\JsonContent(ref: new Model(type: Adherent::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Utilisateur associé introuvable.')]
    #[OA\Response(response: 409, description: 'Un adhérent existe déjà pour cet utilisateur.')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['civilite'], $data['adresse'], $data['code_postal'], $data['ville'])) {
            return $this->json(['error' => 'Les champs civilité, adresse, code postal et ville sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = new Adherent();
        $adherent->setCivilite($data['civilite']);
        $adherent->setAdresse($data['adresse']);
        $adherent->setCodePostal($data['code_postal']);
        $adherent->setVille($data['ville']);
        $adherent->setNom($data['nom'] ?? null);
        $adherent->setPrenom($data['prenom'] ?? null);

        if (array_key_exists('raison_social', $data)) $adherent->setRaisonSocial($data['raison_social']);
        if (array_key_exists('telephone_secondaire', $data)) $adherent->setTelephoneSecondaire($data['telephone_secondaire']);
        if (!empty($data['date_naissance'])) {
            try {
                $adherent->setDateNaissance(new \DateTime($data['date_naissance']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide pour date_naissance.'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (array_key_exists('profession', $data)) $adherent->setProfession($data['profession']);
        if (isset($data['optin_sms'])) $adherent->setOptinSms((bool) $data['optin_sms']);
        if (isset($data['optin_email'])) $adherent->setOptinEmail((bool) $data['optin_email']);
        if (isset($data['est_professionnel'])) $adherent->setEstProfessionnel((bool) $data['est_professionnel']);
        if (isset($data['dispense_adhesion'])) $adherent->setDispenseAdhesion((bool) $data['dispense_adhesion']);

        if (!empty($data['id_utilisateur']) || !empty($data['utilisateur_id'])) {
            $userId = $data['id_utilisateur'] ?? $data['utilisateur_id'];
            $utilisateur = $em->getRepository(Utilisateur::class)->find((int) $userId);
            if (!$utilisateur) {
                return $this->json(['error' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
            }
            $existant = $em->getRepository(Adherent::class)->find((int) $userId);
            if ($existant) {
                return $this->json(['error' => 'Cet utilisateur est déjà associé à un compte adhérent.'], Response::HTTP_CONFLICT);
            }
            $adherent->setUtilisateur($utilisateur);
        }

        $em->persist($adherent);
        $em->flush();

        return $this->json($adherent, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adherents_put', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(summary: 'Modifier un adhérent')]
    #[OA\Patch(summary: 'Modifier partiellement un adhérent')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhérent à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs à mettre à jour sur l\'adhérent',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'civilite', type: 'string', maxLength: 10, example: 'Mme'),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Martin'),
                new OA\Property(property: 'prenom', type: 'string', maxLength: 100, example: 'Sophie'),
                new OA\Property(property: 'raison_social', type: 'string', maxLength: 150, nullable: true, example: null),
                new OA\Property(property: 'adresse', type: 'string', maxLength: 255, example: '18 Rue des Prés'),
                new OA\Property(property: 'code_postal', type: 'string', maxLength: 10, example: '88100'),
                new OA\Property(property: 'ville', type: 'string', maxLength: 150, example: 'Saint-Dié-des-Vosges'),
                new OA\Property(property: 'telephone_secondaire', type: 'string', maxLength: 20, nullable: true, example: '0699887766'),
                new OA\Property(property: 'profession', type: 'string', maxLength: 100, nullable: true, example: 'Responsable logistique'),
                new OA\Property(property: 'optin_sms', type: 'boolean', example: false),
                new OA\Property(property: 'optin_email', type: 'boolean', example: true),
                new OA\Property(property: 'est_professionnel', type: 'boolean', example: false),
                new OA\Property(property: 'dispense_adhesion', type: 'boolean', example: false)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Adhérent mis à jour avec succès.', content: new OA\JsonContent(ref: new Model(type: Adherent::class, groups: ['api_read'])))]
    #[OA\Response(response: 400, description: 'Données invalides.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Adhérent introuvable.')]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $adherent = $em->getRepository(Adherent::class)->find($id);
        if (!$adherent) {
            return $this->json(['error' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('civilite', $data)) $adherent->setCivilite($data['civilite']);
        if (array_key_exists('adresse', $data)) $adherent->setAdresse($data['adresse']);
        if (array_key_exists('code_postal', $data)) $adherent->setCodePostal($data['code_postal']);
        if (array_key_exists('ville', $data)) $adherent->setVille($data['ville']);
        if (array_key_exists('nom', $data)) $adherent->setNom($data['nom']);
        if (array_key_exists('prenom', $data)) $adherent->setPrenom($data['prenom']);
        if (array_key_exists('raison_social', $data)) $adherent->setRaisonSocial($data['raison_social']);
        if (array_key_exists('telephone_secondaire', $data)) $adherent->setTelephoneSecondaire($data['telephone_secondaire']);
        if (array_key_exists('profession', $data)) $adherent->setProfession($data['profession']);
        if (array_key_exists('optin_sms', $data)) $adherent->setOptinSms((bool) $data['optin_sms']);
        if (array_key_exists('optin_email', $data)) $adherent->setOptinEmail((bool) $data['optin_email']);
        if (array_key_exists('est_professionnel', $data)) $adherent->setEstProfessionnel((bool) $data['est_professionnel']);
        if (array_key_exists('dispense_adhesion', $data)) $adherent->setDispenseAdhesion((bool) $data['dispense_adhesion']);

        $em->flush();
        return $this->json($adherent, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adherents_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(summary: 'Supprimer un adhérent')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhérent à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Adhérent supprimé avec succès.')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou expiré.')]
    #[OA\Response(response: 403, description: 'Accès refusé.')]
    #[OA\Response(response: 404, description: 'Adhérent introuvable.')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adherent = $em->getRepository(Adherent::class)->find($id);
        if (!$adherent) {
            return $this->json(['error' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($adherent);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
