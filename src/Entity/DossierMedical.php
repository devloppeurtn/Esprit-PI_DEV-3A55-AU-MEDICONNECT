<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dossier_medical')]
class DossierMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $maladiesChroniques = null;

    #[ORM\OneToOne(targetEntity: Patient::class, inversedBy: 'dossierMedical')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    /** @var Collection<int, Consultation> */
    #[ORM\OneToMany(targetEntity: Consultation::class, mappedBy: 'dossierMedical', cascade: ['persist', 'remove'])]
    private Collection $consultations;

    /** @var Collection<int, DocumentPatient> */
    #[ORM\OneToMany(targetEntity: DocumentPatient::class, mappedBy: 'dossierMedical', cascade: ['persist', 'remove'])]
    private Collection $documents;

    /** @var Collection<int, MedicamentActuel> */
    #[ORM\OneToMany(targetEntity: MedicamentActuel::class, mappedBy: 'dossierMedical', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medicamentsActuels;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->consultations = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->medicamentsActuels = new ArrayCollection();
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

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): static
    {
        $this->allergies = $allergies;
        return $this;
    }

    public function getMaladiesChroniques(): ?string
    {
        return $this->maladiesChroniques;
    }

    public function setMaladiesChroniques(?string $maladiesChroniques): static
    {
        $this->maladiesChroniques = $maladiesChroniques;
        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    /** @return Collection<int, Consultation> */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): static
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setDossierMedical($this);
        }
        return $this;
    }

    /** @return Collection<int, DocumentPatient> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(DocumentPatient $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDossierMedical($this);
        }
        return $this;
    }

    /** @return Collection<int, MedicamentActuel> */
    public function getMedicamentsActuels(): Collection
    {
        return $this->medicamentsActuels;
    }

    public function addMedicamentActuel(MedicamentActuel $medicamentActuel): static
    {
        if (!$this->medicamentsActuels->contains($medicamentActuel)) {
            $this->medicamentsActuels->add($medicamentActuel);
            $medicamentActuel->setDossierMedical($this);
        }
        return $this;
    }

    public function removeMedicamentActuel(MedicamentActuel $medicamentActuel): static
    {
        if ($this->medicamentsActuels->removeElement($medicamentActuel)) {
            if ($medicamentActuel->getDossierMedical() === $this) {
                $medicamentActuel->setDossierMedical(null);
            }
        }
        return $this;
    }
}
