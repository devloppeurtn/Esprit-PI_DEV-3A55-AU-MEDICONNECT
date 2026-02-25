<?php

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDEE = 'VALIDEE';
    case PREPAREE = 'PREPAREE';
    case LIVREE = 'LIVREE';
    case ANNULEE = 'ANNULEE';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En Attente',
            self::VALIDEE => 'Validée',
            self::PREPAREE => 'Préparée',
            self::LIVREE => 'Livrée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::VALIDEE => 'info',
            self::PREPAREE => 'primary',
            self::LIVREE => 'success',
            self::ANNULEE => 'danger',
        };
    }
}
