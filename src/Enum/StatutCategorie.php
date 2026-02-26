<?php

namespace App\Enum;

enum StatutCategorie: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case APPROUVE = 'APPROUVE';
    case REJETE = 'REJETE';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente d\'approbation',
            self::APPROUVE => 'Approuvé',
            self::REJETE => 'Rejeté',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'bg-warning',
            self::APPROUVE => 'bg-success',
            self::REJETE => 'bg-danger',
        };
    }
}
