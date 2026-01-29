<?php

namespace App\Entity;

enum RoleUtilisateur: string
{
    case ADMIN = 'ADMIN';
    case PATIENT = 'PATIENT';
    case MEDECIN = 'MEDECIN';
    case SECRETAIRE = 'SECRETAIRE';
}
