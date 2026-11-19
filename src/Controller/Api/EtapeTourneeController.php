<?php

namespace App\Controller\Api;

use App\Entity\EtapeTournee;
use App\Repository\DepotRepository;
use App\Repository\EtapeTourneeRepository;
use App\Repository\TourneeRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/etape-tournees')]
#[OA\Tag(name: 'Étapes de tournée', description: 'Gestion des étapes et dépôts associés aux tournées')]
final class EtapeTourneeController extends AbstractController
{
    #[Route('', name: 'api_etape_tournees_index', methods: ['GET'])]
    #[OA\Parameter(
        name: 'id_tournee',
        in: 'query',
        description: 'Filtrer les étapes par identifiant de tournée',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 200, description: 'Liste des étapes de tournée')]
    public function index(Request $request, EtapeTourneeRepository $repository): JsonResponse
    {
        $idTournee = $request->query->get('id_tournee') ?? $request->query->get('tournee_id');

        $criteres = [];
        if ($idTournee) {
            $criteres['tournee'] = (int) $idTournee;
        }

        $etapes = $repository->findBy($criteres, ['ordrePassage' => 'ASC']);

        return $this->json($etapes, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{tourneeId}/{depotId}', name: 'api_etape_tournees_show', methods: ['GET'])]
    #[OA\Parameter(name: 'tourneeId', in: 'path', description: 'Identifiant de la tournée', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'depotId', in: 'path', description: 'Identifiant du dépôt', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Détail d\'une étape de tournée')]
    #[OA\Response(response: 404, description: 'Étape introuvable')]
    public function show(int $tourneeId, int $depotId, EtapeTourneeRepository $repository): JsonResponse
    {
        $etape = $repository->findOneBy(['tournee' => $tourneeId, 'depot' => $depotId]);

        if (!$etape) {
            return $this->json(['error' => 'Étape de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($etape, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_etape_tournees_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id_tournee', 'id_depot'],
            properties: [
                new OA\Property(property: 'id_tournee', type: 'integer', example: 1),
                new OA\Property(property: 'id_depot', type: 'integer', example: 2),
                new OA\Property(property: 'ordre_passage', type: 'integer', example: 1),
                new OA\Property(property: 'heure_estimee', type: 'string', format: 'time', example: '08:30:00'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Étape de tournée créée avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou manquantes')]
    #[OA\Response(response: 404, description: 'Tournée ou dépôt introuvable')]
    #[OA\Response(response: 409, description: 'Cette étape existe déjà pour cette tournée et ce dépôt')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        TourneeRepository $tourneeRepository,
        DepotRepository $depotRepository,
        EtapeTourneeRepository $etapeTourneeRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        $idTournee = $donnees['id_tournee'] ?? $donnees['tournee_id'] ?? null;
        $idDepot = $donnees['id_depot'] ?? $donnees['depot_id'] ?? null;

        if (!$idTournee || !$idDepot) {
            return $this->json(
                ['error' => 'Les champs id_tournee et id_depot sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $tournee = $tourneeRepository->find($idTournee);
        if (!$tournee) {
            return $this->json(['error' => 'Tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $depot = $depotRepository->find($idDepot);
        if (!$depot) {
            return $this->json(['error' => 'Dépôt introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $etapeExistante = $etapeTourneeRepository->findOneBy(['tournee' => $tournee, 'depot' => $depot]);
        if ($etapeExistante) {
            return $this->json(
                ['error' => 'Cette étape existe déjà pour cette tournée et ce dépôt.'],
                Response::HTTP_CONFLICT
            );
        }

        $etape = new EtapeTournee();
        $etape->setTournee($tournee);
        $etape->setDepot($depot);

        if (isset($donnees['ordre_passage'])) {
            $etape->setOrdrePassage((int) $donnees['ordre_passage']);
        }

        if (!empty($donnees['heure_estimee'])) {
            try {
                $etape->setHeureEstimee(new \DateTime($donnees['heure_estimee']));
            } catch (\Exception) {
                return $this->json(
                    ['error' => 'Format d\'heure invalide pour heure_estimee (attendu : HH:MM:SS).'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $em->persist($etape);
        $em->flush();

        return $this->json($etape, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{tourneeId}/{depotId}', name: 'api_etape_tournees_update', methods: ['PUT', 'PATCH'])]
    #[OA\Parameter(name: 'tourneeId', in: 'path', description: 'Identifiant de la tournée', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'depotId', in: 'path', description: 'Identifiant du dépôt', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ordre_passage', type: 'integer', example: 2),
                new OA\Property(property: 'heure_estimee', type: 'string', format: 'time', nullable: true, example: '09:15:00'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Étape de tournée mise à jour')]
    #[OA\Response(response: 400, description: 'Format d\'heure invalide')]
    #[OA\Response(response: 404, description: 'Étape introuvable')]
    public function update(
        int $tourneeId,
        int $depotId,
        Request $request,
        EtapeTourneeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $etape = $repository->findOneBy(['tournee' => $tourneeId, 'depot' => $depotId]);

        if (!$etape) {
            return $this->json(['error' => 'Étape de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('ordre_passage', $donnees)) {
            $etape->setOrdrePassage((int) $donnees['ordre_passage']);
        }

        if (array_key_exists('heure_estimee', $donnees)) {
            if ($donnees['heure_estimee'] === null) {
                $etape->setHeureEstimee(null);
            } else {
                try {
                    $etape->setHeureEstimee(new \DateTime($donnees['heure_estimee']));
                } catch (\Exception) {
                    return $this->json(
                        ['error' => 'Format d\'heure invalide pour heure_estimee (attendu : HH:MM:SS).'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        $em->flush();

        return $this->json($etape, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{tourneeId}/{depotId}', name: 'api_etape_tournees_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'tourneeId', in: 'path', description: 'Identifiant de la tournée', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'depotId', in: 'path', description: 'Identifiant du dépôt', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Étape de tournée supprimée')]
    #[OA\Response(response: 404, description: 'Étape introuvable')]
    public function delete(
        int $tourneeId,
        int $depotId,
        EtapeTourneeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $etape = $repository->findOneBy(['tournee' => $tourneeId, 'depot' => $depotId]);

        if (!$etape) {
            return $this->json(['error' => 'Étape de tournée introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($etape);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
