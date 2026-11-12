<?php

namespace App\Entity;

use App\Repository\AdhesionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdhesionRepository::class)]
class Adhesion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_reglement = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $est_a_jour = false;

    #[ORM\ManyToOne(inversedBy: 'adhesions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adherent $adherent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReglement(): ?\DateTime
    {
        return $this->date_reglement;
    }

    public function setDateReglement(?\DateTime $date_reglement): static
    {
        $this->date_reglement = $date_reglement;

        return $this;
    }

    public function isEstAJour(): ?bool
    {
        return $this->est_a_jour;
    }

    public function setEstAJour(?bool $est_a_jour): static
    {
        $this->est_a_jour = $est_a_jour;

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
}
