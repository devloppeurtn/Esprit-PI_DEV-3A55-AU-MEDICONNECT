<?php

namespace App\Entity;

<<<<<<< HEAD
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
=======
<<<<<<< HEAD
=======
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Patient extends Utilisateur
{
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[ORM\OneToOne(targetEntity: DossierMedical::class, mappedBy: 'patient', cascade: ['persist', 'remove'])]
    private ?DossierMedical $dossierMedical = null;

    /** @var Collection<int, RendezVous> */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'patient', cascade: ['persist'])]
    private Collection $rendezVous;

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::PATIENT);
<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $this->rendezVous = new ArrayCollection();
    }

    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(?DossierMedical $dossierMedical): static
    {
        $this->dossierMedical = $dossierMedical;
        if ($dossierMedical && $dossierMedical->getPatient() !== $this) {
            $dossierMedical->setPatient($this);
        }
        return $this;
    }

    /** @return Collection<int, RendezVous> */
    public function getRendezVous(): Collection
    {
        return $this->rendezVous;
    }

    public function addRendezVous(RendezVous $rdv): static
    {
        if (!$this->rendezVous->contains($rdv)) {
            $this->rendezVous->add($rdv);
            $rdv->setPatient($this);
        }
        return $this;
    }

    public function removeRendezVous(RendezVous $rdv): static
    {
        if ($this->rendezVous->removeElement($rdv) && $rdv->getPatient() === $this) {
            $rdv->setPatient(null);
        }
        return $this;
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }
}
