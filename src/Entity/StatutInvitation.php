<?php

namespace App\Entity;

enum StatutInvitation: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case ACCEPTEE = 'ACCEPTEE';
    case REFUSEE = 'REFUSEE';
}
