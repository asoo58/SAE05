<?php

namespace App\Entity;

use App\Repository\StructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
#[Broadcast]
class Structure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom_commercial = null;

    #[ORM\Column(length: 150)]
    private ?string $raison_social = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_url = null;

    #[ORM\Column(length: 255)]
    private ?string $siege_social_adresse = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_gestion = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $bic = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $ics = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $point_depart_lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $point_depart_lng = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $assujettiTva = false;

    /**
     * @var Collection<int, Depot>
     */
    #[ORM\OneToMany(targetEntity: Depot::class, mappedBy: 'structure')]
    private Collection $depots;

    public function __construct()
    {
        $this->depots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomCommercial(): ?string
    {
        return $this->nom_commercial;
    }

    public function setNomCommercial(string $nom_commercial): static
    {
        $this->nom_commercial = $nom_commercial;

        return $this;
    }

    public function getRaisonSocial(): ?string
    {
        return $this->raison_social;
    }

    public function setRaisonSocial(string $raison_social): static
    {
        $this->raison_social = $raison_social;

        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logo_url;
    }

    public function setLogoUrl(?string $logo_url): static
    {
        $this->logo_url = $logo_url;

        return $this;
    }

    public function getSiegeSocialAdresse(): ?string
    {
        return $this->siege_social_adresse;
    }

    public function setSiegeSocialAdresse(string $siege_social_adresse): static
    {
        $this->siege_social_adresse = $siege_social_adresse;

        return $this;
    }

    public function getAdresseGestion(): ?string
    {
        return $this->adresse_gestion;
    }

    public function setAdresseGestion(string $adresse_gestion): static
    {
        $this->adresse_gestion = $adresse_gestion;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getIcs(): ?string
    {
        return $this->ics;
    }

    public function setIcs(?string $ics): static
    {
        $this->ics = $ics;

        return $this;
    }

    public function getPointDepartLat(): ?string
    {
        return $this->point_depart_lat;
    }

    public function setPointDepartLat(string $point_depart_lat): static
    {
        $this->point_depart_lat = $point_depart_lat;

        return $this;
    }

    public function getPointDepartLng(): ?string
    {
        return $this->point_depart_lng;
    }

    public function setPointDepartLng(string $point_depart_lng): static
    {
        $this->point_depart_lng = $point_depart_lng;

        return $this;
    }

    public function isAssujettiTva(): ?bool
    {
        return $this->assujetti_tva;
    }

    public function setAssujettiTva(bool $assujetti_tva): static
    {
        $this->assujetti_tva = $assujetti_tva;

        return $this;
    }

    /**
     * @return Collection<int, Depot>
     */
    public function getDepots(): Collection
    {
        return $this->depots;
    }

    public function addDepot(Depot $depot): static
    {
        if (!$this->depots->contains($depot)) {
            $this->depots->add($depot);
            $depot->setStructure($this);
        }

        return $this;
    }

    public function removeDepot(Depot $depot): static
    {
        if ($this->depots->removeElement($depot)) {
            // set the owning side to null (unless already changed)
            if ($depot->getStructure() === $this) {
                $depot->setStructure(null);
            }
        }

        return $this;
    }
}
