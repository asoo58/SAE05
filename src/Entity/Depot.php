<?php

namespace App\Entity;

use App\Repository\DepotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: DepotRepository::class)]
#[Broadcast]
class Depot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $categorie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $texte_reserve = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mot_de_passe_depot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: false)]
    private ?string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10)]
    private ?string $code_postal = null;

    #[ORM\Column(length: 150)]
    private ?string $ville = null;

    #[ORM\Column]
    private ?int $capacite_max_paniers = null;

    #[ORM\Column(length: 100)]
    private ?string $referent_nom = null;

    #[ORM\Column(length: 20)]
    private ?string $referent_tel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $creneaux_recuperation = null;

    /**
     * @var Collection<int, EtapeTournee>
     */
    #[ORM\OneToMany(targetEntity: EtapeTournee::class, mappedBy: 'depot')]
    private Collection $etapeTournees;

    #[ORM\ManyToOne(inversedBy: 'depots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Structure $structure = null;

    public function __construct()
    {
        $this->etapeTournees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCategorie(): ?int
    {
        return $this->categorie;
    }

    public function setCategorie(int $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getTexteReserve(): ?string
    {
        return $this->texte_reserve;
    }

    public function setTexteReserve(?string $texte_reserve): static
    {
        $this->texte_reserve = $texte_reserve;

        return $this;
    }

    public function getMotDePasseDepot(): ?string
    {
        return $this->mot_de_passe_depot;
    }

    public function setMotDePasseDepot(?string $mot_de_passe_depot): static
    {
        $this->mot_de_passe_depot = $mot_de_passe_depot;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(string $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCapaciteMaxPaniers(): ?int
    {
        return $this->capacite_max_paniers;
    }

    public function setCapaciteMaxPaniers(int $capacite_max_paniers): static
    {
        $this->capacite_max_paniers = $capacite_max_paniers;

        return $this;
    }

    public function getReferentNom(): ?string
    {
        return $this->referent_nom;
    }

    public function setReferentNom(?string $referent_nom): static
    {
        $this->referent_nom = $referent_nom;

        return $this;
    }

    public function getReferentTel(): ?string
    {
        return $this->referent_tel;
    }

    public function setReferentTel(string $referent_tel): static
    {
        $this->referent_tel = $referent_tel;

        return $this;
    }

    public function getCreneauxRecuperation(): ?string
    {
        return $this->creneaux_recuperation;
    }

    public function setCreneauxRecuperation(?string $creneaux_recuperation): static
    {
        $this->creneaux_recuperation = $creneaux_recuperation;

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
            $etapeTournee->setDepot($this);
        }

        return $this;
    }

    public function removeEtapeTournee(EtapeTournee $etapeTournee): static
    {
        if ($this->etapeTournees->removeElement($etapeTournee)) {
            // set the owning side to null (unless already changed)
            if ($etapeTournee->getDepot() === $this) {
                $etapeTournee->setDepot(null);
            }
        }

        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;

        return $this;
    }
}
