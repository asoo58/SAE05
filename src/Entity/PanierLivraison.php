<?php

namespace App\Entity;

use App\Repository\PanierLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierLivraisonRepository::class)]
class PanierLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // statut : VARCHAR(30) DEFAULT 'en_attente'
    #[ORM\Column(length: 20, options: ['default' => 'a_preparer'])]
    private ?string $statut = 'a_preparer';

    #[ORM\Column(nullable: true)]
    private ?\DateTime $date_statut_livre = null;

    #[ORM\ManyToOne]
    private ?LivraisonTournee $livraisonTournee = null;

    #[ORM\ManyToOne]
    private ?Abonnement $abonnement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'depot_id', referencedColumnName: 'id_depot')]
    private ?Depot $depot = null;

    public function getLivraisonTournee(): ?LivraisonTournee { return $this->livraisonTournee; }
    public function setLivraisonTournee(?LivraisonTournee $livraisonTournee): static { $this->livraisonTournee = $livraisonTournee; return $this; }
    public function getAbonnement(): ?Abonnement { return $this->abonnement; }
    public function setAbonnement(?Abonnement $abonnement): static { $this->abonnement = $abonnement; return $this; }
    public function getDepot(): ?Depot { return $this->depot; }
    public function setDepot(?Depot $depot): static { $this->depot = $depot; return $this; }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateStatutLivre(): ?\DateTime
    {
        return $this->date_statut_livre;
    }

    public function setDateStatutLivre(?\DateTime $date_statut_livre): static
    {
        $this->date_statut_livre = $date_statut_livre;

        return $this;
    }
}
