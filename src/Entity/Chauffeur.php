<?php

namespace App\Entity;

use App\Repository\ChauffeurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChauffeurRepository::class)]
class Chauffeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $numero_permis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_validite_permis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_fimo_fco = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroPermis(): ?string
    {
        return $this->numero_permis;
    }

    public function setNumeroPermis(?string $numero_permis): static
    {
        $this->numero_permis = $numero_permis;

        return $this;
    }

    public function getDateValiditePermis(): ?\DateTime
    {
        return $this->date_validite_permis;
    }

    public function setDateValiditePermis(?\DateTime $date_validite_permis): static
    {
        $this->date_validite_permis = $date_validite_permis;

        return $this;
    }

    public function getDateFimoFco(): ?\DateTime
    {
        return $this->date_fimo_fco;
    }

    public function setDateFimoFco(?\DateTime $date_fimo_fco): static
    {
        $this->date_fimo_fco = $date_fimo_fco;

        return $this;
    }
}
