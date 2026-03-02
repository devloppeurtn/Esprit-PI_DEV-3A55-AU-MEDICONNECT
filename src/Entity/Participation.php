<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Participation extends Utilisateur
{
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

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
