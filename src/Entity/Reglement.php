<?php

namespace App\Entity;

use App\Repository\ReglementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReglementRepository::class)]
class Reglement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

   // date_reglement : DATE NOT NULL
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateReglement = null;

    // montant : DECIMAL(8,2) NOT NULL
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $montant = null;

    // mode_reglement : VARCHAR(30) NOT NULL
    #[ORM\Column(length: 30)]
    private ?string $modeReglement = null;

    // statut : VARCHAR(30) DEFAULT 'en_attente'
    #[ORM\Column(length: 30, options: ['default' => 'en_attente'])]
    private ?string $statut = 'en_attente';

    #[ORM\ManyToOne(inversedBy: 'reglements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne(inversedBy: 'reglements')]
    private ?Abonnement $abonnement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReglement(): ?\DateTimeImmutable
    {
        return $this->dateReglement;
    }

    public function setDateReglement(\DateTimeImmutable $dateReglement): static
    {
        $this->dateReglement = $dateReglement;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getModeReglement(): ?string
    {
        return $this->modeReglement;
    }

    public function setModeReglement(string $mode_reglement): static
    {
        $this->modeReglement = $mode_reglement;

        return $this;
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

    public function getAdherent(): ?Adherent
    {
        return $this->adherent;
    }

    public function setAdherent(?Adherent $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): static
    {
        $this->abonnement = $abonnement;

        return $this;
    }
}
