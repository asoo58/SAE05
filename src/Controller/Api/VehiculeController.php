<?php

namespace App\Controller\Api;

use App\Entity\Vehicule;
use App\Repository\StructureRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vehicules')]
#[OA\Tag(name: 'Véhicules', description: 'Gestion de la flotte de véhicules de livraison')]
final class VehiculeController extends AbstractController
{
    #[Route('', name: 'api_vehicules_index', methods: ['GET'])]
    #[OA\Parameter(
        name: 'id_structure',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de structure',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'actif',
        in: 'query',
        required: false,
        description: 'Filtrer par état d\'activation (true/false)',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(response: 200, description: 'Liste des véhicules')]
    public function index(Request $request, VehiculeRepository $repository): JsonResponse
    {
        $criteres = [];

        if ($request->query->has('id_structure') && $request->query->get('id_structure') !== '') {
            $criteres['structure'] = (int) $request->query->get('id_structure');
        }

        if ($request->query->has('actif') && $request->query->get('actif') !== '') {
            $criteres['actif'] = filter_var($request->query->get('actif'), FILTER_VALIDATE_BOOLEAN);
        }

        $vehicules = $repository->findBy($criteres, ['immatriculation' => 'ASC', 'id' => 'ASC']);

        return $this->json($vehicules, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_vehicules_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Détail d\'un véhicule')]
    #[OA\Response(response: 404, description: 'Véhicule introuvable')]
    public function show(int $id, VehiculeRepository $repository): JsonResponse
    {
        $vehicule = $repository->find($id);

        if (!$vehicule) {
            return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($vehicule, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_vehicules_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['immatriculation', 'capacite_panier', 'id_structure'],
            properties: [
                new OA\Property(property: 'immatriculation', type: 'string', maxLength: 15, example: 'AB-123-CD'),
                new OA\Property(property: 'nom_modele', type: 'string', maxLength: 50, nullable: true, example: 'Renault Kangoo Maxi ZE'),
                new OA\Property(property: 'capacite_panier', type: 'integer', example: 45),
                new OA\Property(property: 'actif', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Véhicule créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants')]
    #[OA\Response(response: 409, description: 'Un véhicule avec cette immatriculation existe déjà')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        VehiculeRepository $vehiculeRepository,
        StructureRepository $structureRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['immatriculation']) || !isset($donnees['capacite_panier']) || empty($donnees['id_structure'])) {
            return $this->json(
                ['error' => 'Les champs immatriculation, capacite_panier et id_structure sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_numeric($donnees['capacite_panier']) || (int) $donnees['capacite_panier'] < 0) {
            return $this->json(
                ['error' => 'La capacité de panier doit être un entier positif ou nul.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $structure = $structureRepository->find((int) $donnees['id_structure']);
        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $existant = $vehiculeRepository->findOneBy(['immatriculation' => trim($donnees['immatriculation'])]);
        if ($existant) {
            return $this->json(
                ['error' => 'Un véhicule avec cette immatriculation existe déjà.'],
                Response::HTTP_CONFLICT
            );
        }

        $vehicule = new Vehicule();
        $vehicule->setImmatriculation(trim($donnees['immatriculation']));
        $vehicule->setCapacitePanier((int) $donnees['capacite_panier']);
        $vehicule->setStructure($structure);

        $nomModele = $donnees['nom_modele'] ?? $donnees['nom_model'] ?? null;
        if ($nomModele !== null) {
            $vehicule->setNomModele($nomModele);
        }

        if (array_key_exists('actif', $donnees)) {
            $vehicule->setActif((bool) $donnees['actif']);
        }

        $em->persist($vehicule);
        $em->flush();

        return $this->json($vehicule, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_vehicules_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'immatriculation', type: 'string', maxLength: 15, example: 'AB-123-CD'),
                new OA\Property(property: 'nom_modele', type: 'string', maxLength: 50, nullable: true, example: 'Renault Master E-Tech'),
                new OA\Property(property: 'capacite_panier', type: 'integer', example: 60),
                new OA\Property(property: 'actif', type: 'boolean', example: true),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Véhicule mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Véhicule introuvable')]
    #[OA\Response(response: 409, description: 'Un véhicule avec cette immatriculation existe déjà')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        VehiculeRepository $vehiculeRepository,
        StructureRepository $structureRepository
    ): JsonResponse {
        $vehicule = $vehiculeRepository->find($id);

        if (!$vehicule) {
            return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('immatriculation', $donnees)) {
            $nouvelleImmat = trim($donnees['immatriculation']);
            if (empty($nouvelleImmat)) {
                return $this->json(['error' => 'L\'immatriculation ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }

            if ($nouvelleImmat !== $vehicule->getImmatriculation()) {
                $existant = $vehiculeRepository->findOneBy(['immatriculation' => $nouvelleImmat]);
                if ($existant && $existant->getId() !== $vehicule->getId()) {
                    return $this->json(
                        ['error' => 'Un véhicule avec cette immatriculation existe déjà.'],
                        Response::HTTP_CONFLICT
                    );
                }
            }
            $vehicule->setImmatriculation($nouvelleImmat);
        }

        if (array_key_exists('nom_modele', $donnees) || array_key_exists('nom_model', $donnees)) {
            $nomModele = $donnees['nom_modele'] ?? $donnees['nom_model'];
            $vehicule->setNomModele($nomModele);
        }

        if (array_key_exists('capacite_panier', $donnees)) {
            if (!is_numeric($donnees['capacite_panier']) || (int) $donnees['capacite_panier'] < 0) {
                return $this->json(
                    ['error' => 'La capacité de panier doit être un entier positif ou nul.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $vehicule->setCapacitePanier((int) $donnees['capacite_panier']);
        }

        if (array_key_exists('actif', $donnees)) {
            $vehicule->setActif((bool) $donnees['actif']);
        }

        if (array_key_exists('id_structure', $donnees)) {
            $structure = $structureRepository->find((int) $donnees['id_structure']);
            if (!$structure) {
                return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $vehicule->setStructure($structure);
        }

        $em->flush();

        return $this->json($vehicule, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_vehicules_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Véhicule supprimé')]
    #[OA\Response(response: 404, description: 'Véhicule introuvable')]
    public function delete(
        int $id,
        VehiculeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $vehicule = $repository->find($id);

        if (!$vehicule) {
            return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($vehicule);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
