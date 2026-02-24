<?php

namespace App\Entity;

use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
#[ORM\Table(name: 'commande_produit')]
class CommandeProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(enumType: StatutCommande::class)]
    private StatutCommande $statut = StatutCommande::EN_ATTENTE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantTotal = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignesCommande;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseLivraison = null;
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 32, options: ['default' => 'STANDARD'])]
    private string $deliveryCarrier = 'STANDARD';

    #[ORM\Column(length: 16, options: ['default' => 'MEDIUM'])]
    private string $deliveryTrafficLevel = 'MEDIUM';

    #[ORM\Column(options: ['default' => false])]
    private bool $deliveryCutoffApplied = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveryEtaAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveryCommittedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $deliveryDelayPenaltyPoints = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $deliverySlaBreached = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    public function __construct()
    {
        $this->lignesCommande = new ArrayCollection();
        $this->dateCommande = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getStatut(): StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(?string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignesCommande(): Collection
    {
        return $this->lignesCommande;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): static
    {
        if (!$this->lignesCommande->contains($ligneCommande)) {
            $this->lignesCommande->add($ligneCommande);
            $ligneCommande->setCommande($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): static
    {
        if ($this->lignesCommande->removeElement($ligneCommande)) {
            if ($ligneCommande->getCommande() === $this) {
                $ligneCommande->setCommande(null);
            }
        }

        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?string $modePaiement): static
    {
        $this->modePaiement = $modePaiement;

        return $this;
    }

    public function calculerMontantTotal(): string
    {
        $total = 0;
        foreach ($this->lignesCommande as $ligne) {
            $total += (float)$ligne->getPrixUnitaire() * $ligne->getQuantite();
        }
        return number_format($total, 2, '.', '');
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryCarrier(): string
    {
        return $this->deliveryCarrier;
    }

    public function setDeliveryCarrier(string $deliveryCarrier): static
    {
        $this->deliveryCarrier = strtoupper(trim($deliveryCarrier));

        return $this;
    }

    public function getDeliveryTrafficLevel(): string
    {
        return $this->deliveryTrafficLevel;
    }

    public function setDeliveryTrafficLevel(string $deliveryTrafficLevel): static
    {
        $this->deliveryTrafficLevel = strtoupper(trim($deliveryTrafficLevel));

        return $this;
    }

    public function isDeliveryCutoffApplied(): bool
    {
        return $this->deliveryCutoffApplied;
    }

    public function setDeliveryCutoffApplied(bool $deliveryCutoffApplied): static
    {
        $this->deliveryCutoffApplied = $deliveryCutoffApplied;

        return $this;
    }

    public function getDeliveryEtaAt(): ?\DateTimeImmutable
    {
        return $this->deliveryEtaAt;
    }

    public function setDeliveryEtaAt(?\DateTimeImmutable $deliveryEtaAt): static
    {
        $this->deliveryEtaAt = $deliveryEtaAt;

        return $this;
    }

    public function getDeliveryCommittedAt(): ?\DateTimeImmutable
    {
        return $this->deliveryCommittedAt;
    }

    public function setDeliveryCommittedAt(?\DateTimeImmutable $deliveryCommittedAt): static
    {
        $this->deliveryCommittedAt = $deliveryCommittedAt;

        return $this;
    }

    public function getDeliveryDelayPenaltyPoints(): int
    {
        return $this->deliveryDelayPenaltyPoints;
    }

    public function setDeliveryDelayPenaltyPoints(int $deliveryDelayPenaltyPoints): static
    {
        $this->deliveryDelayPenaltyPoints = max(0, $deliveryDelayPenaltyPoints);

        return $this;
    }

    public function isDeliverySlaBreached(): bool
    {
        return $this->deliverySlaBreached;
    }

    public function setDeliverySlaBreached(bool $deliverySlaBreached): static
    {
        $this->deliverySlaBreached = $deliverySlaBreached;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public function getEstimatedDeliveryDays(): ?int
    {
        if ($this->deliveryEtaAt === null || $this->dateCommande === null) {
            return null;
        }

        $orderedAt = \DateTimeImmutable::createFromInterface($this->dateCommande);
        $seconds = $this->deliveryEtaAt->getTimestamp() - $orderedAt->getTimestamp();
        if ($seconds <= 0) {
            return 1;
        }

        return (int) ceil($seconds / 86400);
    }
}
