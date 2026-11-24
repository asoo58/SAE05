<?php

namespace App\Controller\Api;

use App\Entity\Employe;
use App\Repository\EmployeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/employes')]
#[OA\Tag(name: 'Employés', description: 'Gestion des employés')]
final class EmployeController extends AbstractController
{
    #[Route('', name: 'api_employes_index', methods: ['GET'])]
    #[OA\Get(summary: 'Liste des employés')]
    #[OA\Response(response: 200, description: 'Liste des employés retournée avec succès')]
    public function index(EmployeRepository $repository): JsonResponse
    {
        $employes = $repository->findAll();

        return $this->json($employes, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_employes_show', methods: ['GET'])]
    #[OA\Get(summary: 'Détail d\'un employé')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'employé', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Détail de l\'employé retourné avec succès')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function show(int $id, EmployeRepository $repository): JsonResponse
    {
        $employe = $repository->find($id);

        if (!$employe) {
            return $this->json(['error' => 'Employé introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($employe, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('', name: 'api_employes_create', methods: ['POST'])]
    #[OA\Post(summary: 'Créer un employé')]
    #[OA\RequestBody(
        description: 'Données requises pour créer un employé',
        required: true,
        content: new OA\JsonContent(
            required: ['id_utilisateur'],
            properties: [
                new OA\Property(property: 'id_utilisateur', type: 'integer', example: 1),
                new OA\Property(property: 'poste', type: 'string', maxLength: 50, nullable: true, example: 'Maraîcher'),
                new OA\Property(property: 'matricule', type: 'string', maxLength: 30, nullable: true, example: 'EMP-001'),
                new OA\Property(property: 'date_embauche', type: 'string', format: 'date', nullable: true, example: '2026-01-15'),
                new OA\Property(property: 'actif', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Employé créé avec succès')]
    #[OA\Response(response: 400, description: 'Données invalides ou manquantes')]
    #[OA\Response(response: 404, description: 'Utilisateur introuvable')]
    #[OA\Response(response: 409, description: 'Un employé existe déjà pour cet utilisateur')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepository,
        EmployeRepository $employeRepository
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true) ?? [];

        $idUtilisateur = $donnees['id_utilisateur'] ?? $donnees['utilisateur_id'] ?? null;
        if (!$idUtilisateur) {
            return $this->json(
                ['error' => 'Le champ id_utilisateur est obligatoire.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $utilisateur = $utilisateurRepository->find($idUtilisateur);
        if (!$utilisateur) {
            return $this->json(['error' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $employeExistant = $employeRepository->find($utilisateur->getId());
        if ($employeExistant) {
            return $this->json(
                ['error' => 'Un employé existe déjà pour cet utilisateur.'],
                Response::HTTP_CONFLICT
            );
        }

        $employe = new Employe();
        $employe->setUtilisateur($utilisateur);

        if (isset($donnees['poste'])) {
            $employe->setPoste($donnees['poste']);
        }
        if (isset($donnees['matricule'])) {
            $employe->setMatricule($donnees['matricule']);
        }
        if (!empty($donnees['date_embauche'])) {
            try {
                $employe->setDateEmbauche(new \DateTimeImmutable($donnees['date_embauche']));
            } catch (\Exception) {
                return $this->json(
                    ['error' => 'Format de date invalide pour date_embauche (attendu : YYYY-MM-DD).'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
        if (array_key_exists('actif', $donnees)) {
            $employe->setActif((bool) $donnees['actif']);
        }

        $em->persist($employe);
        $em->flush();

        return $this->json($employe, Response::HTTP_CREATED, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_employes_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(summary: 'Modifier un employé')]
    #[OA\Patch(summary: 'Modifier partiellement un employé')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'employé à modifier', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        description: 'Champs de l\'employé à modifier',
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'poste', type: 'string', maxLength: 50, nullable: true, example: 'Chef de culture'),
                new OA\Property(property: 'matricule', type: 'string', maxLength: 30, nullable: true, example: 'EMP-002'),
                new OA\Property(property: 'date_embauche', type: 'string', format: 'date', nullable: true, example: '2026-02-01'),
                new OA\Property(property: 'actif', type: 'boolean', example: false),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Employé mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Format de date invalide')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function update(
        int $id,
        Request $request,
        EmployeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $employe = $repository->find($id);

        if (!$employe) {
            return $this->json(['error' => 'Employé introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('poste', $donnees)) {
            $employe->setPoste($donnees['poste']);
        }
        if (array_key_exists('matricule', $donnees)) {
            $employe->setMatricule($donnees['matricule']);
        }
        if (array_key_exists('date_embauche', $donnees)) {
            if ($donnees['date_embauche'] === null) {
                $employe->setDateEmbauche(null);
            } else {
                try {
                    $employe->setDateEmbauche(new \DateTimeImmutable($donnees['date_embauche']));
                } catch (\Exception) {
                    return $this->json(
                        ['error' => 'Format de date invalide pour date_embauche (attendu : YYYY-MM-DD).'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }
        if (array_key_exists('actif', $donnees)) {
            $employe->setActif((bool) $donnees['actif']);
        }

        $em->flush();

        return $this->json($employe, Response::HTTP_OK, [], ['groups' => 'api_read']);
    }

    #[Route('/{id}', name: 'api_employes_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Supprimer un employé')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant unique de l\'employé à supprimer', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 204, description: 'Employé supprimé avec succès')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function delete(
        int $id,
        EmployeRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $employe = $repository->find($id);

        if (!$employe) {
            return $this->json(['error' => 'Employé introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($employe);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
