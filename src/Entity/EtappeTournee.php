<?php

namespace App\Entity;

use App\Repository\EtappeTourneeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtappeTourneeRepository::class)]
class EtappeTournee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $ordre_passage = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heure_estimee = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdrePassage(): ?int
    {
        return $this->ordre_passage;
    }

    public function setOrdrePassage(int $ordre_passage): static
    {
        $this->ordre_passage = $ordre_passage;

        return $this;
    }

    public function getHeureEstimee(): ?\DateTime
    {
        return $this->heure_estimee;
    }

    public function setHeureEstimee(?\DateTime $heure_estimee): static
    {
        $this->heure_estimee = $heure_estimee;

        return $this;
    }
}
