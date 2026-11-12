<?php

namespace App\Entity;

use App\Repository\TourneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourneeRepository::class)]
class Tournee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $code_tournee = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $jour_preparation = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $jour_livraison = null;

    #[ORM\Column(length: 7, options: ['default' => '#2E7D32'])]
    private ?string $couleur = '#2E7D32';

    #[ORM\ManyToOne(inversedBy: 'tournees')]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(inversedBy: 'tournees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Saison $saison = null;

    /**
     * @var Collection<int, EtapeTournee>
     */
    #[ORM\OneToMany(targetEntity: EtapeTournee::class, mappedBy: 'tournee')]
    private Collection $etapeTournees;

    public function __construct()
    {
        $this->etapeTournees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeTournee(): ?string
    {
        return $this->code_tournee;
    }

    public function setCodeTournee(string $code_tournee): static
    {
        $this->code_tournee = $code_tournee;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getJourPreparation(): ?string
    {
        return $this->jour_preparation;
    }

    public function setJourPreparation(?string $jour_preparation): static
    {
        $this->jour_preparation = $jour_preparation;

        return $this;
    }

    public function getJourLivraison(): ?string
    {
        return $this->jour_livraison;
    }

    public function setJourLivraison(?string $jour_livraison): static
    {
        $this->jour_livraison = $jour_livraison;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getSaison(): ?Saison
    {
        return $this->saison;
    }

    public function setSaison(?Saison $saison): static
    {
        $this->saison = $saison;

        return $this;
    }

    /**
     * @return Collection<int, EtapeTournee>
     */
    public function getEtapeTournees(): Collection
    {
        return $this->etapeTournees;
    }

    public function addEtapeTournee(EtapeTournee $etapeTournee): static
    {
        if (!$this->etapeTournees->contains($etapeTournee)) {
            $this->etapeTournees->add($etapeTournee);
            $etapeTournee->setTournee($this);
        }

        return $this;
    }

    public function removeEtapeTournee(EtapeTournee $etapeTournee): static
    {
        if ($this->etapeTournees->removeElement($etapeTournee)) {
            // set the owning side to null (unless already changed)
            if ($etapeTournee->getTournee() === $this) {
                $etapeTournee->setTournee(null);
            }
        }

        return $this;
    }
}
