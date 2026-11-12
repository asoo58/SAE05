<?php

namespace App\Entity;

use App\Repository\LivraisonTourneeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonTourneeRepository::class)]
class LivraisonTournee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_livraison = null;

    #[ORM\Column(length: 30, options: ['default' => 'planifiee'])]
    private ?string $statut = 'planifiee';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $itineraire_geojson = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $distance_km = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree_minutes = null;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getItineraireGeojson(): ?string
    {
        return $this->itineraire_geojson;
    }

    public function setItineraireGeojson(?string $itineraire_geojson): static
    {
        $this->itineraire_geojson = $itineraire_geojson;

        return $this;
    }

    public function getDistanceKm(): ?string
    {
        return $this->distance_km;
    }

    public function setDistanceKm(?string $distance_km): static
    {
        $this->distance_km = $distance_km;

        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->duree_minutes;
    }

    public function setDureeMinutes(?int $duree_minutes): static
    {
        $this->duree_minutes = $duree_minutes;

        return $this;
    }
}
