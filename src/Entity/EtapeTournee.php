<?php

namespace App\Entity;

use App\Repository\EtapeTourneeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeTourneeRepository::class)]
class EtapeTournee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'etapeTournees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournee $tournee = null;

    #[ORM\ManyToOne(inversedBy: 'etapeTournees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Depot $depot = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournee(): ?Tournee
    {
        return $this->tournee;
    }

    public function setTournee(?Tournee $tournee): static
    {
        $this->tournee = $tournee;

        return $this;
    }

    public function getDepot(): ?Depot
    {
        return $this->depot;
    }

    public function setDepot(?Depot $depot): static
    {
        $this->depot = $depot;

        return $this;
    }
}
