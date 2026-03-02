<?php

namespace App\Entity;

enum RoleParticipation: string
{
    case ORGANISATEUR = 'ORGANISATEUR';
    case INTERVENANT = 'INTERVENANT';
    case PARTICIPANT = 'PARTICIPANT';
}
