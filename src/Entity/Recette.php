<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $instructions = null;

    #[ORM\Column(nullable: true)]
    private ?int $temps_preparation_min = null;

    #[ORM\Column(nullable: true)]
    private ?int $temps_cuisson_min = null;

    #[ORM\Column]
    private ?int $nb_personnes = null;

    #[ORM\Column]
    private ?bool $partage_national = null;
// tsv_recherche : TSVECTOR
    #[ORM\Column(columnDefinition: 'TSVECTOR', nullable: true)]
    private ?string $tsvRecherche = null;

    // embedding : VECTOR(768)
    #[ORM\Column(columnDefinition: 'VECTOR(768)', nullable: true)]
    private ?string $embedding = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getTempsPreparationMin(): ?int
    {
        return $this->temps_preparation_min;
    }

    public function setTempsPreparationMin(?int $temps_preparation_min): static
    {
        $this->temps_preparation_min = $temps_preparation_min;

        return $this;
    }

    public function getTempsCuissonMin(): ?int
    {
        return $this->temps_cuisson_min;
    }

    public function setTempsCuissonMin(?int $temps_cuisson_min): static
    {
        $this->temps_cuisson_min = $temps_cuisson_min;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nb_personnes;
    }

    public function setNbPersonnes(int $nb_personnes): static
    {
        $this->nb_personnes = $nb_personnes;

        return $this;
    }

    public function isPartageNational(): ?bool
    {
        return $this->partage_national;
    }

    public function setPartageNational(bool $partage_national): static
    {
        $this->partage_national = $partage_national;

        return $this;
    }

    public function getTsvRecherche(): ?string
    {
        return $this->tsvRecherche;
    }

    public function setTsvRecherche(?string $tsvRecherche): static
    {
        $this->tsvRecherche = $tsvRecherche;

        return $this;
    }

    public function getEmbedding(): ?string
    {
        return $this->embedding;
    }

    public function setEmbedding(?string $embedding): static
    {
        $this->embedding = $embedding;

        return $this;
    }
}
