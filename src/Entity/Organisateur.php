<?php

namespace App\Entity;

<<<<<<< HEAD
use Doctrine\DBAL\Types\Types;
=======
<<<<<<< HEAD
=======
use Doctrine\DBAL\Types\Types;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organisateur extends Utilisateur
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
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::ORGANISATEUR);
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
}
