<?php

namespace App\Entity;

use App\Repository\UtilisateurGroupeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UtilisateurGroupeRepository::class)]
class UtilisateurGroupe
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'groupes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Groupe $groupe = null;

    #[Groups(['api_read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTime $date_affectation = null;

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getGroupe(): ?Groupe { return $this->groupe; }
    public function setGroupe(?Groupe $groupe): static { $this->groupe = $groupe; return $this; }

    public function getDateAffectation(): ?\DateTime
    {
        return $this->date_affectation;
    }

    public function setDateAffectation(?\DateTime $date_affectation): static
    {
        $this->date_affectation = $date_affectation;

        return $this;
    }
}
