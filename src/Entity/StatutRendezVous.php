<?php

namespace App\Entity;

enum StatutRendezVous: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRME = 'CONFIRME';
    case ANNULE = 'ANNULE';
    case TERMINE = 'TERMINE';
}
