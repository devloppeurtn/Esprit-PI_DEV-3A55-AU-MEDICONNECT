<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ordonnance')]
class Ordonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenuPrescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $medicament = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $methodeUtilisation = null;

    #[ORM\ManyToOne(targetEntity: Consultation::class, inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Consultation $consultation = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContenuPrescription(): ?string
    {
        return $this->contenuPrescription;
    }

    public function setContenuPrescription(?string $contenuPrescription): static
    {
        $this->contenuPrescription = $contenuPrescription;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getMedicament(): ?string
    {
        return $this->medicament;
    }

    public function setMedicament(?string $medicament): static
    {
        $this->medicament = $medicament;
        return $this;
    }

    public function getMethodeUtilisation(): ?string
    {
        return $this->methodeUtilisation;
    }

    public function setMethodeUtilisation(?string $methodeUtilisation): static
    {
        $this->methodeUtilisation = $methodeUtilisation;
        return $this;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;
        return $this;
    }
}
