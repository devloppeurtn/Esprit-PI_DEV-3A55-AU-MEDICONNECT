<?php

namespace App\Entity;

<<<<<<< HEAD
=======
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
>>>>>>> isramedi
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Medecin extends Utilisateur
{
<<<<<<< HEAD
=======
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

>>>>>>> isramedi
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseCabinet = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $numeroLicence = null;

<<<<<<< HEAD
=======
    #[ORM\OneToMany(targetEntity: Secretaire::class, mappedBy: 'medecin')]
    private Collection $secretaires;

    #[ORM\OneToMany(targetEntity: Invitation::class, mappedBy: 'medecin', cascade: ['persist'])]
    private Collection $invitations;

    /** @var Collection<int, RendezVous> */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'medecin', cascade: ['persist'])]
    private Collection $rendezVous;

    /** @var Collection<int, Consultation> */
    #[ORM\OneToMany(targetEntity: Consultation::class, mappedBy: 'medecin', cascade: ['persist'])]
    private Collection $consultations;

>>>>>>> isramedi
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::MEDECIN);
<<<<<<< HEAD
=======
        $this->secretaires = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->rendezVous = new ArrayCollection();
        $this->consultations = new ArrayCollection();
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
            $rdv->setMedecin($this);
        }
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
            $consultation->setMedecin($this);
        }
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
>>>>>>> isramedi
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getAdresseCabinet(): ?string
    {
        return $this->adresseCabinet;
    }

    public function setAdresseCabinet(?string $adresseCabinet): static
    {
        $this->adresseCabinet = $adresseCabinet;
        return $this;
    }

    public function getNumeroLicence(): ?string
    {
        return $this->numeroLicence;
    }

    public function setNumeroLicence(?string $numeroLicence): static
    {
        $this->numeroLicence = $numeroLicence;
        return $this;
    }
<<<<<<< HEAD
=======

    /** @return Collection<int, Secretaire> */
    public function getSecretaires(): Collection
    {
        return $this->secretaires;
    }

    public function addSecretaire(Secretaire $secretaire): static
    {
        if (!$this->secretaires->contains($secretaire)) {
            $this->secretaires->add($secretaire);
            $secretaire->setMedecin($this);
        }
        return $this;
    }

    public function removeSecretaire(Secretaire $secretaire): static
    {
        if ($this->secretaires->removeElement($secretaire)) {
            if ($secretaire->getMedecin() === $this) {
                $secretaire->setMedecin(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Invitation> */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addInvitation(Invitation $invitation): static
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setMedecin($this);
        }
        return $this;
    }
>>>>>>> isramedi
}
