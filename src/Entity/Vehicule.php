<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15, unique: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom_model = null;

    #[ORM\Column]
    private ?int $capacite_panier = null;

    #[ORM\Column (options: ['default' => true])]
    private ?bool $actif = true;

    /**
     * @var Collection<int, Tournee>
     */
    #[ORM\OneToMany(targetEntity: Tournee::class, mappedBy: 'vehicule')]
    private Collection $tournees;

    public function __construct()
    {
        $this->tournees = new ArrayCollection();
    }

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
        return $this->nom_model;
    }

    public function setNomModel(?string $nom_model): static
    {
        $this->nom_model = $nom_model;

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

    /**
     * @return Collection<int, Tournee>
     */
    public function getTournees(): Collection
    {
        return $this->tournees;
    }

    public function addTournee(Tournee $tournee): static
    {
        if (!$this->tournees->contains($tournee)) {
            $this->tournees->add($tournee);
            $tournee->setVehicule($this);
        }

        return $this;
    }

    public function removeTournee(Tournee $tournee): static
    {
        if ($this->tournees->removeElement($tournee)) {
            // set the owning side to null (unless already changed)
            if ($tournee->getVehicule() === $this) {
                $tournee->setVehicule(null);
            }
        }

        return $this;
    }
}
