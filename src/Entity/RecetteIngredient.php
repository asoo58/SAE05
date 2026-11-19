<?php

namespace App\Entity;

use App\Repository\RecetteIngredientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RecetteIngredientRepository::class)]
class RecetteIngredient
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ingredients')]
    #[ORM\JoinColumn(name: 'id_recette', nullable: false, onDelete: 'CASCADE')]
    private ?Recette $recette = null;

    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(name: 'id_ingredient', nullable: false, onDelete: 'RESTRICT')]
    private ?Ingredient $ingredient = null;

    #[Groups(['api_read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $quantite = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unite = null;

    public function getRecette(): ?Recette { return $this->recette; }
    public function setRecette(?Recette $recette): static { $this->recette = $recette; return $this; }
    public function getIngredient(): ?Ingredient { return $this->ingredient; }
    public function setIngredient(?Ingredient $ingredient): static { $this->ingredient = $ingredient; return $this; }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    #[Groups(['api_read'])]
    public function getIdRecette(): ?int
    {
        return $this->recette?->getId();
    }

    #[Groups(['api_read'])]
    public function getIdIngredient(): ?int
    {
        return $this->ingredient?->getId();
    }
}
