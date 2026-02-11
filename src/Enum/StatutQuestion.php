<?php

namespace App\Enum;

enum StatutQuestion: string
{
    case IA_PROPOSE = 'IA_PROPOSE';
    case VALIDE_MEDECIN = 'VALIDE_MEDECIN';

    public function getLabel(): string
    {
        return match($this) {
            self::IA_PROPOSE => 'Proposé par IA',
            self::VALIDE_MEDECIN => 'Validé par Médecin',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::IA_PROPOSE => 'badge-warning',
            self::VALIDE_MEDECIN => 'badge-success',
        };
    }
}
