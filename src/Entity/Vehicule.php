<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
class Vehicule
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 15, unique: true)]
    private ?string $immatriculation = null;

    #[Groups(['api_read'])]
    #[ORM\Column(name: 'nom_modele', length: 50, nullable: true)]
    private ?string $nom_modele = null;

    #[Groups(['api_read'])]
    #[ORM\Column]
    private ?int $capacite_panier = null;

    #[Groups(['api_read'])]
    #[ORM\Column (options: ['default' => true])]
    private ?bool $actif = true;

    #[Groups(['api_read'])]
    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    #[ORM\JoinColumn(name: 'id_structure', nullable: false)]
    private ?Structure $structure = null;


    public function __construct()
    {
    }

    public function getStructure(): ?Structure { return $this->structure; }
    public function setStructure(?Structure $structure): static { $this->structure = $structure; return $this; }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getNomModel(): ?string
    {
        return $this->nom_modele;
    }

    public function setNomModel(?string $nom_model): static
    {
        $this->nom_modele = $nom_model;

        return $this;
    }

    public function getNomModele(): ?string
    {
        return $this->nom_modele;
    }

    public function setNomModele(?string $nom_modele): static
    {
        $this->nom_modele = $nom_modele;

        return $this;
    }

    public function getCapacitePanier(): ?int
    {
        return $this->capacite_panier;
    }

    public function setCapacitePanier(int $capacite_panier): static
    {
        $this->capacite_panier = $capacite_panier;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

}
