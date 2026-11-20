<?php

namespace App\Controller\Api;

use App\Entity\Tournee;
use App\Repository\SaisonRepository;
use App\Repository\StructureRepository;
use App\Repository\TourneeRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournees')]
#[OA\Tag(name: 'Tournées', description: 'Gestion des tournées de livraison')]
final class TourneeController extends AbstractController
{
    #[Route('', name: 'api_tournees_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des tournées')]
    #[OA\Parameter(
        name: 'id_structure',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de structure',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'id_saison',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de saison',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'id_vehicule',
        in: 'query',
        required: false,
        description: 'Filtrer par identifiant de véhicule',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Liste des tournées')]
    public function index(Request $request, TourneeRepository $repository): JsonResponse
    {
        $criteres = [];

        if ($request->query->has('id_structure') && $request->query->get('id_structure') !== '') {
            $criteres['structure'] = (int) $request->query->get('id_structure');
        }

        if ($request->query->has('id_saison') && $request->query->get('id_saison') !== '') {
            $criteres['saison'] = (int) $request->query->get('id_saison');
        }

        if ($request->query->has('id_vehicule') && $request->query->get('id_vehicule') !== '') {
            $criteres['vehicule'] = (int) $request->query->get('id_vehicule');
        }

        $tournees = $repository->findBy($criteres, ['nom' => 'ASC', 'id' => 'ASC']);

        return $this->json($tournees, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_tournees_show', methods: ['GET'])]
    #[OA\Get(summary: "Détail d'une tournée")]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de la tournée',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Détail d\'une tournée')]
    #[OA\Response(response: 404, description: 'Tournée introuvable')]
    public function show(int $id, TourneeRepository $repository): JsonResponse
    {
        $tournee = $repository->find($id);

        if (!$tournee) {
            return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($tournee, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_tournees_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer une tournée')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code_tournee', 'id_structure'],
            properties: [
                new OA\Property(property: 'code_tournee', type: 'string', maxLength: 20, example: 'TOUR-NORD'),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, nullable: true, example: 'Tournée Nord'),
                new OA\Property(property: 'jour_preparation', type: 'string', maxLength: 15, nullable: true, example: 'Mardi'),
                new OA\Property(property: 'jour_livraison', type: 'string', maxLength: 15, nullable: true, example: 'Mercredi'),
                new OA\Property(property: 'couleur', type: 'string', maxLength: 7, example: '#2E7D32'),
                new OA\Property(property: 'id_vehicule', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_saison', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Tournée créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou champs obligatoires manquants')]
    #[OA\Response(response: 409, description: 'Une tournée avec ce code existe déjà pour cette structure')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        TourneeRepository $tourneeRepository,
        StructureRepository $structureRepository,
        VehiculeRepository $vehiculeRepository,
        SaisonRepository $saisonRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        if (empty($donnees['code_tournee']) || empty($donnees['id_structure'])) {
            return $this->json(
                ['error' => 'Les champs code_tournee et id_structure sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $structure = $structureRepository->find((int) $donnees['id_structure']);
        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $existant = $tourneeRepository->findOneBy([
            'structure' => $structure,
            'code_tournee' => $donnees['code_tournee'],
        ]);

        if ($existant) {
            return $this->json(
                ['error' => 'Une tournée avec ce code existe déjà pour cette structure.'],
                Response::HTTP_CONFLICT
            );
        }

        $tournee = new Tournee();
        $tournee->setCodeTournee($donnees['code_tournee']);
        $tournee->setStructure($structure);

        if (array_key_exists('nom', $donnees)) {
            $tournee->setNom($donnees['nom']);
        }

        if (array_key_exists('jour_preparation', $donnees)) {
            $tournee->setJourPreparation($donnees['jour_preparation']);
        }

        if (array_key_exists('jour_livraison', $donnees)) {
            $tournee->setJourLivraison($donnees['jour_livraison']);
        }

        if (!empty($donnees['couleur'])) {
            $tournee->setCouleur($donnees['couleur']);
        }

        if (!empty($donnees['id_vehicule'])) {
            $vehicule = $vehiculeRepository->find((int) $donnees['id_vehicule']);
            if (!$vehicule) {
                return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $tournee->setVehicule($vehicule);
        }

        if (!empty($donnees['id_saison'])) {
            $saison = $saisonRepository->find((int) $donnees['id_saison']);
            if (!$saison) {
                return $this->json(['error' => 'Saison introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $tournee->setSaison($saison);
        }

        $em->persist($tournee);
        $em->flush();

        return $this->json($tournee, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_tournees_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier une tournée')]
    #[OA\Patch(summary: 'Modifier partiellement une tournée')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de la tournée',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code_tournee', type: 'string', maxLength: 20, example: 'TOUR-NORD-MAJ'),
                new OA\Property(property: 'id_structure', type: 'integer', example: 1),
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, nullable: true, example: 'Tournée Nord Modifiée'),
                new OA\Property(property: 'jour_preparation', type: 'string', maxLength: 15, nullable: true, example: 'Mardi'),
                new OA\Property(property: 'jour_livraison', type: 'string', maxLength: 15, nullable: true, example: 'Jeudi'),
                new OA\Property(property: 'couleur', type: 'string', maxLength: 7, example: '#1E88E5'),
                new OA\Property(property: 'id_vehicule', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'id_saison', type: 'integer', nullable: true, example: 1),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Tournée mise à jour avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 404, description: 'Tournée introuvable')]
    #[OA\Response(response: 409, description: 'Une tournée avec ce code existe déjà pour cette structure')]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TourneeRepository $tourneeRepository,
        StructureRepository $structureRepository,
        VehiculeRepository $vehiculeRepository,
        SaisonRepository $saisonRepository
    ): JsonResponse {
        $tournee = $tourneeRepository->find($id);

        if (!$tournee) {
            return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        $nouveauCode = array_key_exists('code_tournee', $donnees) ? $donnees['code_tournee'] : $tournee->getCodeTournee();
        $nouvelleStructure = $tournee->getStructure();

        if (array_key_exists('id_structure', $donnees)) {
            $structureTrouvee = $structureRepository->find((int) $donnees['id_structure']);
            if (!$structureTrouvee) {
                return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $nouvelleStructure = $structureTrouvee;
        }

        if ($nouveauCode !== $tournee->getCodeTournee() || $nouvelleStructure !== $tournee->getStructure()) {
            $conflit = $tourneeRepository->findOneBy([
                'structure' => $nouvelleStructure,
                'code_tournee' => $nouveauCode,
            ]);

            if ($conflit && $conflit->getId() !== $tournee->getId()) {
                return $this->json(
                    ['error' => 'Une tournée avec ce code existe déjà pour cette structure.'],
                    Response::HTTP_CONFLICT
                );
            }
        }

        if (array_key_exists('code_tournee', $donnees)) {
            if (empty($donnees['code_tournee'])) {
                return $this->json(['error' => 'Le code_tournee ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            $tournee->setCodeTournee($donnees['code_tournee']);
        }

        if (array_key_exists('id_structure', $donnees)) {
            $tournee->setStructure($nouvelleStructure);
        }

        if (array_key_exists('nom', $donnees)) {
            $tournee->setNom($donnees['nom']);
        }

        if (array_key_exists('jour_preparation', $donnees)) {
            $tournee->setJourPreparation($donnees['jour_preparation']);
        }

        if (array_key_exists('jour_livraison', $donnees)) {
            $tournee->setJourLivraison($donnees['jour_livraison']);
        }

        if (array_key_exists('couleur', $donnees)) {
            $tournee->setCouleur($donnees['couleur'] ?? '#2E7D32');
        }

        if (array_key_exists('id_vehicule', $donnees)) {
            if ($donnees['id_vehicule'] === null) {
                $tournee->setVehicule(null);
            } else {
                $vehicule = $vehiculeRepository->find((int) $donnees['id_vehicule']);
                if (!$vehicule) {
                    return $this->json(['error' => 'Véhicule introuvable.'], Response::HTTP_BAD_REQUEST);
                }
                $tournee->setVehicule($vehicule);
            }
        }

        if (array_key_exists('id_saison', $donnees)) {
            if ($donnees['id_saison'] === null) {
                $tournee->setSaison(null);
            } else {
                $saison = $saisonRepository->find((int) $donnees['id_saison']);
                if (!$saison) {
                    return $this->json(['error' => 'Saison introuvable.'], Response::HTTP_BAD_REQUEST);
                }
                $tournee->setSaison($saison);
            }
        }

        $em->flush();

        return $this->json($tournee, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_tournees_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer une tournée')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de la tournée',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 204, description: 'Tournée supprimée')]
    #[OA\Response(response: 404, description: 'Tournée introuvable')]
    public function delete(
        int $id,
        TourneeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $tournee = $repository->find($id);

        if (!$tournee) {
            return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($tournee);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
