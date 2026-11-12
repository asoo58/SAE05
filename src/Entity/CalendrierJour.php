<?php

namespace App\Entity;

use App\Repository\CalendrierJourRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendrierJourRepository::class)]
class CalendrierJour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_livraison = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_preparation = null;

    #[ORM\Column (options: ['default' => true])]
    private ?bool $est_livrable;

    #[ORM\Column (options: ['default' => false])]
    private ?bool $est_decale;

    #[ORM\Column(length: 255)]
    private ?string $motif_decalage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateLivraison(): ?\DateTime
    {
        return $this->date_livraison;
    }

    public function setDateLivraison(\DateTime $date_livraison): static
    {
        $this->date_livraison = $date_livraison;

        return $this;
    }

    public function getDatePreparation(): ?\DateTime
    {
        return $this->date_preparation;
    }

    public function setDatePreparation(\DateTime $date_preparation): static
    {
        $this->date_preparation = $date_preparation;

        return $this;
    }

    public function isEstLivrable(): ?bool
    {
        return $this->est_livrable;
    }

    public function setEstLivrable(bool $est_livrable): static
    {
        $this->est_livrable = $est_livrable;

        return $this;
    }

    public function isEstDecale(): ?bool
    {
        return $this->est_decale;
    }

    public function setEstDecale(bool $est_decale): static
    {
        $this->est_decale = $est_decale;

        return $this;
    }

    public function getMotifDecalage(): ?string
    {
        return $this->motif_decalage;
    }

    public function setMotifDecalage(?string $motif_decalage): static
    {
        $this->motif_decalage = $motif_decalage;

        return $this;
    }
}
