<?php

namespace App\Controller\Api;

use App\Entity\Adherent;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\Property;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/adherents')]
#[OA\Tag(name: 'Adhérents', description: 'Gestion des membres et clients de l\'association')]
class AdherentController extends AbstractController
{
    #[Route('', name: 'api_adherents_get', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des adhérents.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Adherent::class, groups: ['api_read'])))
    )]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $adherents = $em->getRepository(Adherent::class)->findAll();
        return $this->json($adherents, 200, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_adherents_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\RequestBody(
        description: 'Données pour créer un nouvel adhérent',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'civilite', type: 'string', example: 'Mme'),
                new OA\Property(property: 'adresse', type: 'string', example: '12 Rue des Lilas'),
                new OA\Property(property: 'code_postal', type: 'string', example: '88100'),
                new OA\Property(property: 'ville', type: 'string', example: 'Saint-Dié-des-Vosges'),
                new OA\Property(property: 'telephone_secondaire', type: 'string', example: '0611223344', nullable: true),
                new OA\Property(property: 'optin_sms', type: 'boolean', example: true),
                new OA\Property(property: 'optin_email', type: 'boolean', example: true),
                new OA\Property(property: 'nom', type: 'string', example: 'Martin', nullable: true),
                new OA\Property(property: 'prenom', type: 'string', example: 'Jean', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Adhérent créé avec succès.')]
    #[OA\Response(response: 400, description: 'Données invalides (champs obligatoires manquants).')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires (selon ta structure SQL)
        if (!isset($data['civilite'], $data['adresse'], $data['code_postal'], $data['ville'])) {
            return $this->json(['message' => 'Les champs civilité, adresse, code postal et ville sont obligatoires.'], 400);
        }

        $adherent = new Adherent();
        $adherent->setCivilite($data['civilite']);
        $adherent->setAdresse($data['adresse']);
        $adherent->setCodePostal($data['code_postal']);
        $adherent->setVille($data['ville']);
        $adherent->setNom($data['nom'] ?? null);
        $adherent->setPrenom($data['prenom'] ?? null);
        
        // Champs optionnels ou avec valeurs par défaut
        if (isset($data['raison_social'])) $adherent->setRaisonSocial($data['raison_social']);
        if (isset($data['telephone_secondaire'])) $adherent->setTelephoneSecondaire($data['telephone_secondaire']);
        if (isset($data['date_naissance'])) $adherent->setDateNaissance(new \DateTime($data['date_naissance']));
        if (isset($data['profession'])) $adherent->setProfession($data['profession']);
        if (isset($data['optin_sms'])) $adherent->setOptinSms($data['optin_sms']);
        if (isset($data['optin_email'])) $adherent->setOptinEmail($data['optin_email']);
        if (isset($data['est_professionnel'])) $adherent->setEstProfessionnel($data['est_professionnel']);
        if (isset($data['dispense_adhesion'])) $adherent->setDispenseAdhesion($data['dispense_adhesion']);

        $em->persist($adherent);
        $em->flush();

        return $this->json($adherent, 201, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adherents_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'adhérent à modifier', required: true)]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $adherent = $em->getRepository(Adherent::class)->find($id);
        if (!$adherent) return $this->json(['message' => 'Adhérent non trouvé'], 404);

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['civilite'])) $adherent->setCivilite($data['civilite']);
        if (isset($data['adresse'])) $adherent->setAdresse($data['adresse']);
        if (isset($data['code_postal'])) $adherent->setCodePostal($data['code_postal']);
        if (isset($data['ville'])) $adherent->setVille($data['ville']);
        if (isset($data['telephone_secondaire'])) $adherent->setTelephoneSecondaire($data['telephone_secondaire']);
        if (isset($data['optin_sms'])) $adherent->setOptinSms($data['optin_sms']);
        if (isset($data['optin_email'])) $adherent->setOptinEmail($data['optin_email']);
        if (isset($data['nom'])) $adherent->setNom($data['nom']);
        if (isset($data['prenom'])) $adherent->setPrenom($data['prenom']);

        $em->flush();
        return $this->json($adherent, 200, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_adherents_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $adherent = $em->getRepository(Adherent::class)->find($id);
        if (!$adherent) return $this->json(['message' => 'Adhérent non trouvé'], 404);

        $em->remove($adherent);
        $em->flush();

        return $this->json(null, 204);
    }
}