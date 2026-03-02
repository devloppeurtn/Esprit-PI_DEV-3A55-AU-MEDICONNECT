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
class Medecin extends Utilisateur
{
<<<<<<< HEAD
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

=======
<<<<<<< HEAD
=======
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseCabinet = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $numeroLicence = null;

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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

<<<<<<< HEAD
    /** @var Collection<int, AvisMedecin> */
    #[ORM\OneToMany(targetEntity: AvisMedecin::class, mappedBy: 'medecin', cascade: ['persist', 'remove'])]
    private Collection $avisMedecin;

=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::MEDECIN);
<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $this->secretaires = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->rendezVous = new ArrayCollection();
        $this->consultations = new ArrayCollection();
<<<<<<< HEAD
        $this->avisMedecin = new ArrayCollection();
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1

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
<<<<<<< HEAD

    /** @return Collection<int, AvisMedecin> */
    public function getAvisMedecin(): Collection
    {
        return $this->avisMedecin;
    }

    public function addAvisMedecin(AvisMedecin $avis): static
    {
        if (!$this->avisMedecin->contains($avis)) {
            $this->avisMedecin->add($avis);
            $avis->setMedecin($this);
        }
        return $this;
    }

    public function removeAvisMedecin(AvisMedecin $avis): static
    {
        if ($this->avisMedecin->removeElement($avis)) {
            if ($avis->getMedecin() === $this) {
                $avis->setMedecin(null);
            }
        }
        return $this;
    }
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
}
