<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/structures')]
#[OA\Tag(name: 'Structures', description: 'Gestion des structures associatives et jardins de Cocagne')]
final class StructureController extends AbstractController
{
    #[Route('', name: 'api_structures_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des structures')]
    #[OA\Response(response: 200, description: 'Liste des structures retournée avec succès')]
    public function index(StructureRepository $repository): JsonResponse
    {
        $structures = $repository->findBy([], ['nom_commercial' => 'ASC']);

        return $this->json($structures, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_structures_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'une structure')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la structure', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail de la structure retourné avec succès')]
    #[OA\Response(response: 404, description: 'Structure introuvable')]
    public function show(int $id, StructureRepository $repository): JsonResponse
    {
        $structure = $repository->find($id);

        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($structure, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_structures_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer une structure')]
    #[OA\RequestBody(
        description: 'Données requises pour créer une structure',
        required: true,
        content: new OA\JsonContent(
            required: ['nom_commercial', 'raison_social', 'siege_social_adresse'],
            properties: [
                new OA\Property(property: 'nom_commercial', type: 'string', maxLength: 150, example: 'Les Jardins de Cocagne'),
                new OA\Property(property: 'raison_social', type: 'string', maxLength: 150, example: 'Association Jardins de Cocagne'),
                new OA\Property(property: 'siege_social_adresse', type: 'string', maxLength: 255, example: '12 rue de la Ferme, 88100 Saint-Dié'),
                new OA\Property(property: 'logo_url', type: 'string', maxLength: 255, nullable: true, example: 'https://example.com/logo.png'),
                new OA\Property(property: 'adresse_gestion', type: 'string', maxLength: 255, nullable: true, example: null),
                new OA\Property(property: 'telephone', type: 'string', maxLength: 20, nullable: true, example: '0329000000'),
                new OA\Property(property: 'email', type: 'string', maxLength: 150, nullable: true, example: 'contact@cocagne.fr'),
                new OA\Property(property: 'siret', type: 'string', maxLength: 14, nullable: true, example: '12345678900012'),
                new OA\Property(property: 'bic', type: 'string', maxLength: 11, nullable: true, example: 'BNPAFRPPXXX'),
                new OA\Property(property: 'iban', type: 'string', maxLength: 34, nullable: true, example: 'FR7630004000011234567890143'),
                new OA\Property(property: 'ics', type: 'string', maxLength: 35, nullable: true, example: 'FR12ZZZ123456'),
                new OA\Property(property: 'point_depart_lat', type: 'string', nullable: true, example: '48.2894500'),
                new OA\Property(property: 'point_depart_lng', type: 'string', nullable: true, example: '6.9497800'),
                new OA\Property(property: 'assujetti_tva', type: 'boolean', example: false),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Structure créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants')]
    #[OA\Response(response: 409, description: 'Ce numéro SIRET est déjà utilisé')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        StructureRepository $repository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['nom_commercial']) || empty($donnees['raison_social']) || empty($donnees['siege_social_adresse'])) {
            return $this->json(
                ['error' => 'Les champs nom_commercial, raison_social et siege_social_adresse sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $siret = !empty($donnees['siret']) ? trim((string) $donnees['siret']) : null;
        if ($siret !== null) {
            $existant = $repository->findOneBy(['siret' => $siret]);
            if ($existant) {
                return $this->json(
                    ['error' => 'Une structure avec ce numéro SIRET existe déjà.'],
                    Response::HTTP_CONFLICT
                );
            }
        }

        $structure = new Structure();
        $structure->setNomCommercial(trim((string) $donnees['nom_commercial']));
        $structure->setRaisonSocial(trim((string) $donnees['raison_social']));
        $structure->setSiegeSocialAdresse(trim((string) $donnees['siege_social_adresse']));
        $structure->setSiret($siret);

        if (array_key_exists('logo_url', $donnees)) {
            $structure->setLogoUrl($donnees['logo_url']);
        }
        if (array_key_exists('adresse_gestion', $donnees)) {
            $structure->setAdresseGestion($donnees['adresse_gestion']);
        }
        if (array_key_exists('telephone', $donnees)) {
            $structure->setTelephone($donnees['telephone']);
        }
        if (array_key_exists('email', $donnees)) {
            $structure->setEmail($donnees['email']);
        }
        if (array_key_exists('bic', $donnees)) {
            $structure->setBic($donnees['bic']);
        }
        if (array_key_exists('iban', $donnees)) {
            $structure->setIban($donnees['iban']);
        }
        if (array_key_exists('ics', $donnees)) {
            $structure->setIcs($donnees['ics']);
        }
        if (array_key_exists('point_depart_lat', $donnees)) {
            $structure->setPointDepartLat($donnees['point_depart_lat'] !== null ? (string) $donnees['point_depart_lat'] : null);
        }
        if (array_key_exists('point_depart_lng', $donnees)) {
            $structure->setPointDepartLng($donnees['point_depart_lng'] !== null ? (string) $donnees['point_depart_lng'] : null);
        }
        if (array_key_exists('assujetti_tva', $donnees)) {
            $structure->setAssujettiTva((bool) $donnees['assujetti_tva']);
        }

        $em->persist($structure);
        $em->flush();

        return $this->json($structure, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_structures_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier une structure')]
    #[OA\Patch(summary: 'Modifier partiellement une structure')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la structure à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs de la structure à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom_commercial', type: 'string', maxLength: 150, example: 'Cocagne Lorraine'),
                new OA\Property(property: 'raison_social', type: 'string', maxLength: 150, example: 'Association Cocagne'),
                new OA\Property(property: 'siege_social_adresse', type: 'string', maxLength: 255, example: '14 rue de la Ferme'),
                new OA\Property(property: 'telephone', type: 'string', maxLength: 20, nullable: true, example: '0329112233'),
                new OA\Property(property: 'email', type: 'string', maxLength: 150, nullable: true, example: 'nouveau@cocagne.fr'),
                new OA\Property(property: 'assujetti_tva', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Structure mise à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Structure introuvable')]
    #[OA\Response(response: 409, description: 'Ce numéro SIRET est déjà utilisé')]
    public function update(
        int $id,
        Request $request,
        StructureRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $structure = $repository->find($id);

        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nom_commercial', $donnees)) {
            $nom = trim((string) $donnees['nom_commercial']);
            if ($nom === '') {
                return $this->json(['error' => 'Le champ nom_commercial ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $structure->setNomCommercial($nom);
        }

        if (array_key_exists('raison_social', $donnees)) {
            $raison = trim((string) $donnees['raison_social']);
            if ($raison === '') {
                return $this->json(['error' => 'Le champ raison_social ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $structure->setRaisonSocial($raison);
        }

        if (array_key_exists('siege_social_adresse', $donnees)) {
            $adresse = trim((string) $donnees['siege_social_adresse']);
            if ($adresse === '') {
                return $this->json(['error' => 'Le champ siege_social_adresse ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $structure->setSiegeSocialAdresse($adresse);
        }

        if (array_key_exists('siret', $donnees)) {
            $nouveauSiret = $donnees['siret'] !== null ? trim((string) $donnees['siret']) : null;
            if ($nouveauSiret !== null && $nouveauSiret !== $structure->getSiret()) {
                $doublon = $repository->findOneBy(['siret' => $nouveauSiret]);
                if ($doublon) {
                    return $this->json(['error' => 'Ce numéro SIRET est déjà utilisé par une autre structure.'], Response::HTTP_CONFLICT);
                }
            }
            $structure->setSiret($nouveauSiret);
        }

        if (array_key_exists('logo_url', $donnees)) {
            $structure->setLogoUrl($donnees['logo_url']);
        }
        if (array_key_exists('adresse_gestion', $donnees)) {
            $structure->setAdresseGestion($donnees['adresse_gestion']);
        }
        if (array_key_exists('telephone', $donnees)) {
            $structure->setTelephone($donnees['telephone']);
        }
        if (array_key_exists('email', $donnees)) {
            $structure->setEmail($donnees['email']);
        }
        if (array_key_exists('bic', $donnees)) {
            $structure->setBic($donnees['bic']);
        }
        if (array_key_exists('iban', $donnees)) {
            $structure->setIban($donnees['iban']);
        }
        if (array_key_exists('ics', $donnees)) {
            $structure->setIcs($donnees['ics']);
        }
        if (array_key_exists('point_depart_lat', $donnees)) {
            $structure->setPointDepartLat($donnees['point_depart_lat'] !== null ? (string) $donnees['point_depart_lat'] : null);
        }
        if (array_key_exists('point_depart_lng', $donnees)) {
            $structure->setPointDepartLng($donnees['point_depart_lng'] !== null ? (string) $donnees['point_depart_lng'] : null);
        }
        if (array_key_exists('assujetti_tva', $donnees)) {
            $structure->setAssujettiTva((bool) $donnees['assujetti_tva']);
        }

        $em->flush();

        return $this->json($structure, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_structures_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer une structure')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de la structure à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Structure supprimée avec succès')]
    #[OA\Response(response: 404, description: 'Structure introuvable')]
    public function delete(
        int $id,
        StructureRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $structure = $repository->find($id);

        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($structure);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
