<?php

namespace App\Entity;

use App\Repository\ProgressionUtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProgressionUtilisateurRepository::class)]
#[ORM\Table(name: 'progression_utilisateur')]
class ProgressionUtilisateur
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: CategorieSante::class, inversedBy: 'progressions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieSante $categorieSante = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $scoreMax = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $nbTentatives = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $badgeNom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateObtention = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $estComplete = false;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getCategorieSante(): ?CategorieSante
    {
        return $this->categorieSante;
    }

    public function setCategorieSante(?CategorieSante $categorieSante): self
    {
        $this->categorieSante = $categorieSante;

        return $this;
    }

    public function getScoreMax(): int
    {
        return $this->scoreMax;
    }

    public function setScoreMax(int $scoreMax): self
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    public function getNbTentatives(): int
    {
        return $this->nbTentatives;
    }

    public function setNbTentatives(int $nbTentatives): self
    {
        $this->nbTentatives = $nbTentatives;

        return $this;
    }

    public function incrementNbTentatives(): self
    {
        $this->nbTentatives++;

        return $this;
    }

    public function getBadgeNom(): ?string
    {
        return $this->badgeNom;
    }

    public function setBadgeNom(?string $badgeNom): self
    {
        $this->badgeNom = $badgeNom;

        return $this;
    }

    public function getDateObtention(): ?\DateTimeInterface
    {
        return $this->dateObtention;
    }

    public function setDateObtention(?\DateTimeInterface $dateObtention): self
    {
        $this->dateObtention = $dateObtention;

        return $this;
    }

    public function isEstComplete(): bool
    {
        return $this->estComplete;
    }

    public function setEstComplete(bool $estComplete): self
    {
        $this->estComplete = $estComplete;

        return $this;
    }
}
