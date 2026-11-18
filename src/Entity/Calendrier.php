<?php

namespace App\Entity;

use App\Repository\CalendrierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendrierRepository::class)]
class Calendrier
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[Groups(['api_read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;
    #[Groups(['api_read'])]
    #[ORM\Column(length: 50)]
    private ?string $frequence = null;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'calendrier')]
    private Collection $abonnements;

    #[ORM\ManyToOne]
    private ?Tournee $tournee = null;

    /** @var Collection<int, CalendrierJour> */
    #[ORM\OneToMany(targetEntity: CalendrierJour::class, mappedBy: 'calendrier', cascade: ['persist'], orphanRemoval: true)]
    private Collection $jours;

    public function __construct()
    {
        $this->abonnements = new ArrayCollection();
        $this->jours = new ArrayCollection();
    }

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

    public function getFrequence(): ?string
    {
        return $this->frequence;
    }

    public function setFrequence(string $frequence): static
    {
        $this->frequence = $frequence;

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setCalendrier($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getCalendrier() === $this) {
                $abonnement->setCalendrier(null);
            }
        }

        return $this;
    }

    public function getTournee(): ?Tournee { return $this->tournee; }
    public function setTournee(?Tournee $tournee): static { $this->tournee = $tournee; return $this; }
    public function getJours(): Collection { return $this->jours; }
}
