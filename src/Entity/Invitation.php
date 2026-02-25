<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invitation')]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Medecin $medecin = null;

    #[ORM\ManyToOne(targetEntity: Secretaire::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Secretaire $secretaire = null;

    #[ORM\Column(type: Types::STRING, enumType: StatutInvitation::class)]
    private ?StatutInvitation $statut = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateReponse = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->statut = StatutInvitation::EN_ATTENTE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getSecretaire(): ?Secretaire
    {
        return $this->secretaire;
    }

    public function setSecretaire(?Secretaire $secretaire): static
    {
        $this->secretaire = $secretaire;
        return $this;
    }

    public function getStatut(): ?StatutInvitation
    {
        return $this->statut;
    }

    public function setStatut(StatutInvitation $statut): static
    {
        $this->statut = $statut;
        $this->dateReponse = new \DateTimeImmutable();
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateReponse(): ?\DateTimeImmutable
    {
        return $this->dateReponse;
    }
}
