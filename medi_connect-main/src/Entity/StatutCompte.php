<?php

namespace App\Entity;

enum StatutCompte: string
{
    case ACTIF = 'ACTIF';
    case BANNI = 'BANNI';
    case SUSPENDU = 'SUSPENDU';
}
