<?php

namespace App\Entity;

use App\Repository\UtilisateurGroupeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurGroupeRepository::class)]
class UtilisateurGroupe
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'groupes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Groupe $groupe = null;

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
