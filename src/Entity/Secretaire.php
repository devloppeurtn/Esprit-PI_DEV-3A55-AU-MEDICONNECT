<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Secretaire extends Utilisateur
{
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

<<<<<<< HEAD
=======
    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'secretaires')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Medecin $medecin = null;

    /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Invitation> */
    #[ORM\OneToMany(targetEntity: Invitation::class, mappedBy: 'secretaire')]
    private \Doctrine\Common\Collections\Collection $invitations;

>>>>>>> isramedi
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::SECRETAIRE);
<<<<<<< HEAD
=======
        $this->invitations = new \Doctrine\Common\Collections\ArrayCollection();
>>>>>>> isramedi
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
<<<<<<< HEAD
=======

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }

    /** @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Invitation> */
    public function getInvitations(): \Doctrine\Common\Collections\Collection
    {
        return $this->invitations;
    }
>>>>>>> isramedi
}
