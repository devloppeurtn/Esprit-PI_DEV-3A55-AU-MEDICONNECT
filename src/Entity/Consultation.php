<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $diagnostic = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\ManyToOne(targetEntity: DossierMedical::class, inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DossierMedical $dossierMedical = null;

    #[ORM\OneToOne(targetEntity: RendezVous::class, inversedBy: 'consultation')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?RendezVous $rendezVous = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Medecin $medecin = null;

    /** @var Collection<int, Ordonnance> */
    #[ORM\OneToMany(targetEntity: Ordonnance::class, mappedBy: 'consultation', cascade: ['persist', 'remove'])]
    private Collection $ordonnances;

    /** @var Collection<int, RapportMedical> */
    #[ORM\OneToMany(targetEntity: RapportMedical::class, mappedBy: 'consultation', cascade: ['persist', 'remove'])]
    private Collection $rapportsMedicaux;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->ordonnances = new ArrayCollection();
        $this->rapportsMedicaux = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(?string $diagnostic): static
    {
        $this->diagnostic = $diagnostic;
        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;
        return $this;
    }

    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(?DossierMedical $dossierMedical): static
    {
        $this->dossierMedical = $dossierMedical;
        return $this;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): static
    {
        $this->rendezVous = $rendezVous;
        return $this;
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

    /** @return Collection<int, Ordonnance> */
    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }

    public function addOrdonnance(Ordonnance $ordonnance): static
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->setConsultation($this);
        }
        return $this;
    }

    /** @return Collection<int, RapportMedical> */
    public function getRapportsMedicaux(): Collection
    {
        return $this->rapportsMedicaux;
    }

    public function addRapportMedical(RapportMedical $rapportMedical): static
    {
        if (!$this->rapportsMedicaux->contains($rapportMedical)) {
            $this->rapportsMedicaux->add($rapportMedical);
            $rapportMedical->setConsultation($this);
        }
        return $this;
    }
}
