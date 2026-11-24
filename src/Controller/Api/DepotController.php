<?php

namespace App\Controller\Api;

use App\Entity\Depot;
use App\Repository\DepotRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/depots', name: 'api_depots_')]
#[OA\Tag(name: 'Dépôts', description: 'Gestion des points de dépôt pour la récupération des paniers')]
class DepotController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Liste des dépôts',
        parameters: [
            new OA\Parameter(name: 'ville', in: 'query', required: false, description: 'Filtrer par ville', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'categorie', in: 'query', required: false, description: 'Filtrer par catégorie', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'structure_id', in: 'query', required: false, description: 'Filtrer par identifiant de structure', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des dépôts retournée avec succès')
        ]
    )]
    public function index(Request $request, DepotRepository $depotRepository): JsonResponse
    {
        $criteria = [];

        if ($request->query->has('ville') && $request->query->get('ville') !== '') {
            $criteria['ville'] = $request->query->get('ville');
        }
        if ($request->query->has('categorie') && $request->query->get('categorie') !== '') {
            $criteria['categorie'] = (int) $request->query->get('categorie');
        }
        if ($request->query->has('structure_id') && $request->query->get('structure_id') !== '') {
            $criteria['structure'] = (int) $request->query->get('structure_id');
        }

        $depots = empty($criteria)
            ? $depotRepository->findAll()
            : $depotRepository->findBy($criteria);

        return $this->json($depots, Response::HTTP_OK, [], [
            'groups' => ['api_read'],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        summary: 'Détail d\'un dépôt',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Identifiant du dépôt', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails du dépôt retournés avec succès'),
            new OA\Response(response: 404, description: 'Dépôt introuvable')
        ]
    )]
    public function show(int $id, DepotRepository $depotRepository): JsonResponse
    {
        $depot = $depotRepository->find($id);

        if (!$depot) {
            return $this->json(['error' => 'Dépôt introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($depot, Response::HTTP_OK, [], [
            'groups' => ['api_read'],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer un dépôt',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nom', 'categorie', 'latitude', 'longitude', 'adresse', 'code_postal', 'ville', 'capacite_max_paniers', 'structure_id'],
                properties: [
                    new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Dépôt Centre-Ville Saint-Dié'),
                    new OA\Property(property: 'categorie', type: 'integer', example: 1),
                    new OA\Property(property: 'texte_reserve', type: 'string', nullable: true, example: 'Accès réservé aux adhérents Cocagne'),
                    new OA\Property(property: 'mot_de_passe_depot', type: 'string', nullable: true, example: 'COCAGNE2026'),
                    new OA\Property(property: 'latitude', type: 'string', example: '48.1889000'),
                    new OA\Property(property: 'longitude', type: 'string', example: '6.9503000'),
                    new OA\Property(property: 'adresse', type: 'string', maxLength: 255, example: '12 Rue des Jardins'),
                    new OA\Property(property: 'code_postal', type: 'string', maxLength: 10, example: '88100'),
                    new OA\Property(property: 'ville', type: 'string', maxLength: 100, example: 'Saint-Dié-des-Vosges'),
                    new OA\Property(property: 'capacite_max_paniers', type: 'integer', example: 40),
                    new OA\Property(property: 'referent_nom', type: 'string', maxLength: 100, nullable: true, example: 'Dupont Marie'),
                    new OA\Property(property: 'referent_tel', type: 'string', maxLength: 20, nullable: true, example: '06 11 22 33 44'),
                    new OA\Property(property: 'creneaux_recuperation', type: 'string', maxLength: 255, nullable: true, example: 'Mercredi 17h-19h'),
                    new OA\Property(property: 'structure_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Dépôt créé avec succès'),
            new OA\Response(response: 400, description: 'Données invalides ou structure manquante')
        ]
    )]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        StructureRepository $structureRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = [
            'nom',
            'categorie',
            'latitude',
            'longitude',
            'adresse',
            'code_postal',
            'ville',
            'capacite_max_paniers',
            'structure_id',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                return $this->json(['error' => "Champ obligatoire manquant : {$field}"], Response::HTTP_BAD_REQUEST);
            }
        }

        $structure = $structureRepository->find((int) $data['structure_id']);
        if (!$structure) {
            return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $depot = new Depot();
        $depot
            ->setNom((string) $data['nom'])
            ->setCategorie((int) $data['categorie'])
            ->setTexteReserve(isset($data['texte_reserve']) ? (string) $data['texte_reserve'] : null)
            ->setMotDePasseDepot(isset($data['mot_de_passe_depot']) ? (string) $data['mot_de_passe_depot'] : null)
            ->setLatitude((string) $data['latitude'])
            ->setLongitude((string) $data['longitude'])
            ->setAdresse((string) $data['adresse'])
            ->setCodePostal((string) $data['code_postal'])
            ->setVille((string) $data['ville'])
            ->setCapaciteMaxPaniers((int) $data['capacite_max_paniers'])
            ->setReferentNom(isset($data['referent_nom']) ? (string) $data['referent_nom'] : null)
            ->setReferentTel(isset($data['referent_tel']) ? (string) $data['referent_tel'] : null)
            ->setCreneauxRecuperation(isset($data['creneaux_recuperation']) ? (string) $data['creneaux_recuperation'] : null)
            ->setStructure($structure);

        $em->persist($depot);
        $em->flush();

        return $this->json($depot, Response::HTTP_CREATED, [], [
            'groups' => ['api_read'],
        ]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un dépôt')]
    #[OA\Patch(summary: 'Modifier partiellement un dépôt')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant du dépôt à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs du dépôt à mettre à jour',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', maxLength: 100, example: 'Dépôt Centre-Ville Modifié'),
                new OA\Property(property: 'categorie', type: 'integer', example: 1),
                new OA\Property(property: 'texte_reserve', type: 'string', nullable: true, example: 'Accès réservé aux adhérents'),
                new OA\Property(property: 'mot_de_passe_depot', type: 'string', nullable: true, example: 'NOUVEAU_MDP_2026'),
                new OA\Property(property: 'latitude', type: 'string', example: '48.1889000'),
                new OA\Property(property: 'longitude', type: 'string', example: '6.9503000'),
                new OA\Property(property: 'adresse', type: 'string', maxLength: 255, example: '14 Rue des Jardins'),
                new OA\Property(property: 'code_postal', type: 'string', maxLength: 10, example: '88100'),
                new OA\Property(property: 'ville', type: 'string', maxLength: 100, example: 'Saint-Dié-des-Vosges'),
                new OA\Property(property: 'capacite_max_paniers', type: 'integer', example: 50),
                new OA\Property(property: 'referent_nom', type: 'string', maxLength: 100, nullable: true, example: 'Dupont Pierre'),
                new OA\Property(property: 'referent_tel', type: 'string', maxLength: 20, nullable: true, example: '06 99 88 77 66'),
                new OA\Property(property: 'creneaux_recuperation', type: 'string', maxLength: 255, nullable: true, example: 'Jeudi 17h-19h'),
                new OA\Property(property: 'structure_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Dépôt mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Corps JSON invalide ou structure introuvable')]
    #[OA\Response(response: 404, description: 'Dépôt introuvable')]
    public function update(
        int $id,
        Request $request,
        DepotRepository $depotRepository,
        StructureRepository $structureRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $depot = $depotRepository->find($id);
        if (!$depot) {
            return $this->json(['error' => 'Dépôt introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('nom', $data)) {
            $depot->setNom((string) $data['nom']);
        }
        if (array_key_exists('categorie', $data)) {
            $depot->setCategorie((int) $data['categorie']);
        }
        if (array_key_exists('texte_reserve', $data)) {
            $depot->setTexteReserve($data['texte_reserve'] !== null ? (string) $data['texte_reserve'] : null);
        }
        if (array_key_exists('mot_de_passe_depot', $data)) {
            $depot->setMotDePasseDepot($data['mot_de_passe_depot'] !== null ? (string) $data['mot_de_passe_depot'] : null);
        }
        if (array_key_exists('latitude', $data)) {
            $depot->setLatitude((string) $data['latitude']);
        }
        if (array_key_exists('longitude', $data)) {
            $depot->setLongitude((string) $data['longitude']);
        }
        if (array_key_exists('adresse', $data)) {
            $depot->setAdresse((string) $data['adresse']);
        }
        if (array_key_exists('code_postal', $data)) {
            $depot->setCodePostal((string) $data['code_postal']);
        }
        if (array_key_exists('ville', $data)) {
            $depot->setVille((string) $data['ville']);
        }
        if (array_key_exists('capacite_max_paniers', $data)) {
            $depot->setCapaciteMaxPaniers((int) $data['capacite_max_paniers']);
        }
        if (array_key_exists('referent_nom', $data)) {
            $depot->setReferentNom($data['referent_nom'] !== null ? (string) $data['referent_nom'] : null);
        }
        if (array_key_exists('referent_tel', $data)) {
            $depot->setReferentTel($data['referent_tel'] !== null ? (string) $data['referent_tel'] : null);
        }
        if (array_key_exists('creneaux_recuperation', $data)) {
            $depot->setCreneauxRecuperation($data['creneaux_recuperation'] !== null ? (string) $data['creneaux_recuperation'] : null);
        }
        if (array_key_exists('structure_id', $data)) {
            $structure = $structureRepository->find((int) $data['structure_id']);
            if (!$structure) {
                return $this->json(['error' => 'Structure introuvable.'], Response::HTTP_BAD_REQUEST);
            }
            $depot->setStructure($structure);
        }

        $em->flush();

        return $this->json($depot, Response::HTTP_OK, [], [
            'groups' => ['api_read'],
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer un dépôt',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Identifiant du dépôt à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Dépôt supprimé avec succès'),
            new OA\Response(response: 404, description: 'Dépôt introuvable')
        ]
    )]
    public function delete(int $id, DepotRepository $depotRepository, EntityManagerInterface $em): JsonResponse
    {
        $depot = $depotRepository->find($id);
        if (!$depot) {
            return $this->json(['error' => 'Dépôt introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($depot);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
