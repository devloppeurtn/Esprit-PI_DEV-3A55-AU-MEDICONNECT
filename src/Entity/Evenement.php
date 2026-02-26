<?php

namespace App\Entity;

use App\Enum\StatutEvenement;
use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    /** Statut de validation : EN_ATTENTE (défaut), VALIDE (accepté par admin), REFUSE */
    #[ORM\Column(type: Types::STRING, length: 20, enumType: StatutEvenement::class)]
    private StatutEvenement $statut = StatutEvenement::EN_ATTENTE;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $organisateur = null;

    #[ORM\ManyToOne(targetEntity: Admin::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Admin $approuvePar = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approuveAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $eventTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeImmutable $eventDate): self
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getEventTime(): ?string
    {
        return $this->eventTime;
    }

    public function setEventTime(?string $eventTime): self
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    public function getStatut(): StatutEvenement
    {
        return $this->statut;
    }

    public function setStatut(StatutEvenement $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getOrganisateur(): ?Utilisateur
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Utilisateur $organisateur): self
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    public function getApprouvePar(): ?Admin
    {
        return $this->approuvePar;
    }

    public function setApprouvePar(?Admin $approuvePar): self
    {
        $this->approuvePar = $approuvePar;
        return $this;
    }

    public function getApprouveAt(): ?\DateTimeImmutable
    {
        return $this->approuveAt;
    }

    public function setApprouveAt(?\DateTimeImmutable $approuveAt): self
    {
        $this->approuveAt = $approuveAt;
        return $this;
    }
}
