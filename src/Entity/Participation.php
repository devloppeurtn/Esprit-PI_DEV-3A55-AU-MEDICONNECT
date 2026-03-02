<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Participation extends Utilisateur
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
    #[ORM\Column(type: Types::STRING, enumType: RoleParticipation::class)]
    private ?RoleParticipation $roleDansEvenement = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $presenceConfirmee = false;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::PARTICIPATION);
        $this->roleDansEvenement = RoleParticipation::PARTICIPANT;
    }

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function getRoleDansEvenement(): ?RoleParticipation
    {
        return $this->roleDansEvenement;
    }

    public function setRoleDansEvenement(RoleParticipation $roleDansEvenement): static
    {
        $this->roleDansEvenement = $roleDansEvenement;
        return $this;
    }

    public function isPresenceConfirmee(): bool
    {
        return $this->presenceConfirmee;
    }

    public function setPresenceConfirmee(bool $presenceConfirmee): static
    {
        $this->presenceConfirmee = $presenceConfirmee;
        return $this;
    }
}
