<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    #[Groups(['api_read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['api_read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_debut = null;

    #[Groups(['api_read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_fin = null;

    #[Groups(['api_read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0'])]
    private ?string $remisePourcentage = '0';

    #[Groups(['api_read'])]
    #[ORM\Column(length: 20, options: ['default' => 'actif'])]
    private ?string $statut = 'actif';

    /**
     * @var Collection<int, Reglement>
     */
    #[ORM\OneToMany(targetEntity: Reglement::class, mappedBy: 'abonnement')]
    private Collection $reglements;

    #[Groups(['api_read'])]
    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_adherent')]
    private ?Adherent $adherent = null;

    #[Groups(['api_read'])]
    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_produit')]
    private ?Produit $produit = null;

    #[Groups(['api_read'])]
    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Calendrier $calendrier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_depot', referencedColumnName: 'id_depot', nullable: true)]
    private ?Depot $depot = null;

    public function __construct()
    {
        $this->reglements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getRemisePourcentage(): ?string
    {
        return $this->remisePourcentage;
    }

    public function setRemisePourcentage(string $remisePourcentage): static
    {
        $this->remisePourcentage = $remisePourcentage;
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

    /**
     * @return Collection<int, Reglement>
     */
    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglement $reglement): static
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements->add($reglement);
            $reglement->setAbonnement($this);
        }
        return $this;
    }

    public function removeReglement(Reglement $reglement): static
    {
        if ($this->reglements->removeElement($reglement)) {
            if ($reglement->getAbonnement() === $this) {
                $reglement->setAbonnement(null);
            }
        }
        return $this;
    }

    public function getAdherent(): ?Adherent
    {
        return $this->adherent;
    }

    public function setAdherent(?Adherent $adherent): static
    {
        $this->adherent = $adherent;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getCalendrier(): ?Calendrier
    {
        return $this->calendrier;
    }

    public function setCalendrier(?Calendrier $calendrier): static
    {
        $this->calendrier = $calendrier;
        return $this;
    }

    public function getDepot(): ?Depot { return $this->depot; }
    public function setDepot(?Depot $depot): static { $this->depot = $depot; return $this; }
}