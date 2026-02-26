<?php

namespace App\Entity;

use App\Repository\PlanningMedecinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningMedecinRepository::class)]
class PlanningMedecin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebutMatin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFinMatin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebutApresMidi = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFinApresMidi = null;

    #[ORM\Column]
    private int $dureeConsultation = 30;

    #[ORM\Column(type: Types::JSON)]
    private array $joursOuverture = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];

    #[ORM\OneToOne(inversedBy: 'planning', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medecin $medecin = null;

    public function getId(): ?int { return $this->id; }
    public function getHeureDebutMatin(): ?\DateTimeInterface { return $this->heureDebutMatin; }
    public function setHeureDebutMatin(?\DateTimeInterface $h): self { $this->heureDebutMatin = $h; return $this; }
    public function getHeureFinMatin(): ?\DateTimeInterface { return $this->heureFinMatin; }
    public function setHeureFinMatin(?\DateTimeInterface $h): self { $this->heureFinMatin = $h; return $this; }
    public function getHeureDebutApresMidi(): ?\DateTimeInterface { return $this->heureDebutApresMidi; }
    public function setHeureDebutApresMidi(?\DateTimeInterface $h): self { $this->heureDebutApresMidi = $h; return $this; }
    public function getHeureFinApresMidi(): ?\DateTimeInterface { return $this->heureFinApresMidi; }
    public function setHeureFinApresMidi(?\DateTimeInterface $h): self { $this->heureFinApresMidi = $h; return $this; }
    public function getDureeConsultation(): int { return $this->dureeConsultation; }
    public function setDureeConsultation(int $d): self { $this->dureeConsultation = $d; return $this; }
    public function getJoursOuverture(): array { return $this->joursOuverture; }
    public function setJoursOuverture(array $j): self { $this->joursOuverture = $j; return $this; }
    public function getMedecin(): ?Medecin { return $this->medecin; }
    public function setMedecin(?Medecin $m): self { $this->medecin = $m; return $this; }
}