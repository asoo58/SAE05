<?php

namespace App\Entity;

use App\Repository\UtilisateurGroupeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurGroupeRepository::class)]
class UtilisateurGroupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $date_affectation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

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
