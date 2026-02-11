<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organisateur extends Utilisateur
{
    public function __construct()
    {
        parent::__construct();
        $this->setRole(RoleUtilisateur::ORGANISATEUR);
    }
}
