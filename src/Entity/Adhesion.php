<?php

namespace App\Entity;

use App\Repository\AdhesionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AdhesionRepository::class)]
class Adhesion
{
    #[Groups (['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups (['api_read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_reglement = null;

    #[Groups (['api_read'])]
    #[ORM\Column(options: ['default' => false])]
    private ?bool $est_a_jour = false;
    
    #[Groups (['api_read'])]
    #[ORM\ManyToOne(inversedBy: 'adhesions')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_adherent')]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne]
    private ?TypeAdhesion $typeAdhesion = null;

    #[ORM\ManyToOne]
    private ?Saison $saison = null;

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

    public function getTypeAdhesion(): ?TypeAdhesion { return $this->typeAdhesion; }
    public function setTypeAdhesion(?TypeAdhesion $typeAdhesion): static { $this->typeAdhesion = $typeAdhesion; return $this; }
    public function getSaison(): ?Saison { return $this->saison; }
    public function setSaison(?Saison $saison): static { $this->saison = $saison; return $this; }
}
