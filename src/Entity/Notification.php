<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private ?string $type = 'info';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $lien = null;

    #[ORM\Column(name: 'lue')]
    private ?bool $estLu = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateLecture = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $categorieId = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
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

    // Alias for module 5 notifications
    public function getDestinataire(): ?Utilisateur
    {
        return $this->getUtilisateur();
    }

    // Alias for module 5 notifications
    public function setDestinataire(?Utilisateur $utilisateur): static
    {
        return $this->setUtilisateur($utilisateur);
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(?string $lien): static
    {
        $this->lien = $lien;
        return $this;
    }

    public function isEstLu(): ?bool
    {
        return $this->estLu;
    }

    public function setEstLu(bool $estLu): static
    {
        $this->estLu = $estLu;
        return $this;
    }

    public function isLu(): bool
    {
        return (bool) $this->estLu;
    }

    public function setLu(bool $lu): static
    {
        $this->estLu = $lu;
        if ($lu && !$this->dateLecture) {
            $this->dateLecture = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateLecture(): ?\DateTimeImmutable
    {
        return $this->dateLecture;
    }

    public function setDateLecture(?\DateTimeImmutable $dateLecture): static
    {
        $this->dateLecture = $dateLecture;
        return $this;
    }

    public function getCategorieId(): ?Uuid
    {
        return $this->categorieId;
    }

    public function setCategorieId(?Uuid $categorieId): static
    {
        $this->categorieId = $categorieId;
        return $this;
    }
}
