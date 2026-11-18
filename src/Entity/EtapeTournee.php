<?php

namespace App\Entity;

use App\Repository\EtapeTourneeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeTourneeRepository::class)]
class EtapeTournee
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'etapeTournees')]
    #[ORM\JoinColumn(name: 'id_tournee', nullable: false, onDelete: 'CASCADE')]
    private ?Tournee $tournee = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'etapeTournees')]
    #[ORM\JoinColumn(name: 'id_depot', referencedColumnName: 'id_depot', nullable: false)]
    private ?Depot $depot = null;

    #[ORM\Column(name: 'ordre_passage')]
    private int $ordrePassage = 0;

    #[ORM\Column(name: 'heure_estimee', type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureEstimee = null;

    public function getTournee(): ?Tournee { return $this->tournee; }
    public function setTournee(?Tournee $tournee): static { $this->tournee = $tournee; return $this; }
    public function getDepot(): ?Depot { return $this->depot; }
    public function setDepot(?Depot $depot): static { $this->depot = $depot; return $this; }
    public function getOrdrePassage(): ?int { return $this->ordrePassage; }
    public function setOrdrePassage(int $ordrePassage): static { $this->ordrePassage = $ordrePassage; return $this; }
    public function getHeureEstimee(): ?\DateTime { return $this->heureEstimee; }
    public function setHeureEstimee(?\DateTime $heureEstimee): static { $this->heureEstimee = $heureEstimee; return $this; }
}
