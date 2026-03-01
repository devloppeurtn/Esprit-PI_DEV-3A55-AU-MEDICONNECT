<?php

namespace App\Enum;

enum TypeEvenement: string
{
    case CONFERENCE_EDUCATIVE = 'CONFERENCE_EDUCATIVE';
    case ATELIER_PREVENTION = 'ATELIER_PREVENTION';
    case DEPISTAGE_COMMUNAUTAIRE = 'DEPISTAGE_COMMUNAUTAIRE';
    case ACTION_VOLONTAIRE = 'ACTION_VOLONTAIRE';
    case SEMINAIRE_MEDICAL = 'SEMINAIRE_MEDICAL';
    case FORMATION_INTERNE = 'FORMATION_INTERNE';

    public function label(): string
    {
        return match ($this) {
            self::CONFERENCE_EDUCATIVE => 'Conference educative',
            self::ATELIER_PREVENTION => 'Atelier prevention',
            self::DEPISTAGE_COMMUNAUTAIRE => 'Depistage communautaire',
            self::ACTION_VOLONTAIRE => 'Action volontaire',
            self::SEMINAIRE_MEDICAL => 'Seminaire medical',
            self::FORMATION_INTERNE => 'Formation interne',
        };
    }
}

