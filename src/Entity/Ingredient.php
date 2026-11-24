<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 100, unique: true)]
    private ?string $nom = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rayon = null;

    /** @var Collection<int, RecetteIngredient> */
    #[ORM\OneToMany(targetEntity: RecetteIngredient::class, mappedBy: 'ingredient')]
    private Collection $recettes;

    public function __construct() { $this->recettes = new ArrayCollection(); }
    public function getRecettes(): Collection { return $this->recettes; }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getRayon(): ?string
    {
        return $this->rayon;
    }

    public function setRayon(?string $rayon): static
    {
        $this->rayon = $rayon;

        return $this;
    }
}
