<?php

namespace App\Entity;

<<<<<<< HEAD
=======
use Doctrine\DBAL\Types\Types;
>>>>>>> isramedi
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organisateur extends Utilisateur
{
<<<<<<< HEAD
=======
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

>>>>>>> isramedi
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::ORGANISATEUR);
    }
<<<<<<< HEAD
=======

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }
>>>>>>> isramedi
}
