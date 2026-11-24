<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
class Groupe
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $code_groupe = null;

    #[Groups(['api_read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $libelle = null;

    #[Groups(['api_read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, UtilisateurGroupe> */
    #[ORM\OneToMany(targetEntity: UtilisateurGroupe::class, mappedBy: 'groupe', cascade: ['persist'], orphanRemoval: true)]
    private Collection $utilisateurs;

    public function __construct() { $this->utilisateurs = new ArrayCollection(); }
    public function getUtilisateurs(): Collection { return $this->utilisateurs; }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeGroupe(): ?string
    {
        return $this->code_groupe;
    }

    public function setCodeGroupe(string $code_groupe): static
    {
        $this->code_groupe = $code_groupe;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

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
}
