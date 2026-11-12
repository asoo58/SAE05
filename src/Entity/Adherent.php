<?php

namespace App\Entity;

use App\Repository\AdherentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdherentRepository::class)]
class Adherent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $civilite = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $raison_social = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10)]
    private ?string $code_postal = null;

    #[ORM\Column(length: 150)]
    private ?string $ville = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone_secondaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_naissance = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $profession = null;

  #[ORM\Column(options: ['default' => false])]
    private ?bool $optinSms = false;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $optinEmail = true;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $estProfessionnel = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $dispenseAdhesion = false;

    /**
     * @var Collection<int, Reglement>
     */
    #[ORM\OneToMany(targetEntity: Reglement::class, mappedBy: 'adherent')]
    private Collection $reglements;

    /**
     * @var Collection<int, Adhesion>
     */
    #[ORM\OneToMany(targetEntity: Adhesion::class, mappedBy: 'adherent')]
    private Collection $adhesions;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'adherent')]
    private Collection $abonnements;

    public function __construct()
    {
        $this->reglements = new ArrayCollection();
        $this->adhesions = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    public function setCivilite(string $civilite): static
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getRaisonSocial(): ?string
    {
        return $this->raison_social;
    }

    public function setRaisonSocial(?string $raison_social): static
    {
        $this->raison_social = $raison_social;

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

    public function getTelephoneSecondaire(): ?string
    {
        return $this->telephone_secondaire;
    }

    public function setTelephoneSecondaire(?string $telephone_secondaire): static
    {
        $this->telephone_secondaire = $telephone_secondaire;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function isOptinEmail(): ?bool
    {
        return $this->optinEmail;
    }

    public function setOptinEmail(bool $optinEmail): static
    {
        $this->optinEmail = $optinEmail;

        return $this;
    }

    public function isOptinSms(): ?bool
    {
        return $this->optinSms;
    }

    public function setOptinSms(bool $optinSms): static
    {
        $this->optinSms = $optinSms;

        return $this;
    }

    public function isEstProfessionnel(): ?bool
    {
        return $this->estProfessionnel;
    }

    public function setEstProfessionnel(bool $est_professionnel): static
    {
        $this->estProfessionnel = $est_professionnel;

        return $this;
    }

    public function isDispenseAdhesion(): ?bool
    {
        return $this->dispenseAdhesion;
    }

    public function setDispenseAdhesion(bool $dispense_adhesion): static
    {
        $this->dispenseAdhesion = $dispense_adhesion;

        return $this;
    }

    /**
     * @return Collection<int, Reglement>
     */
    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglement $reglement): static
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements->add($reglement);
            $reglement->setAdherent($this);
        }

        return $this;
    }

    public function removeReglement(Reglement $reglement): static
    {
        if ($this->reglements->removeElement($reglement)) {
            // set the owning side to null (unless already changed)
            if ($reglement->getAdherent() === $this) {
                $reglement->setAdherent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Adhesion>
     */
    public function getAdhesions(): Collection
    {
        return $this->adhesions;
    }

    public function addAdhesion(Adhesion $adhesion): static
    {
        if (!$this->adhesions->contains($adhesion)) {
            $this->adhesions->add($adhesion);
            $adhesion->setAdherent($this);
        }

        return $this;
    }

    public function removeAdhesion(Adhesion $adhesion): static
    {
        if ($this->adhesions->removeElement($adhesion)) {
            // set the owning side to null (unless already changed)
            if ($adhesion->getAdherent() === $this) {
                $adhesion->setAdherent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setAdherent($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getAdherent() === $this) {
                $abonnement->setAdherent(null);
            }
        }

        return $this;
    }
}
