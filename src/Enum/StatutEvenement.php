<?php

namespace App\Enum;

enum StatutEvenement: string
{
    case EN_ATTENTE = 'EN_ATTENTE';  // En attente de validation admin
    case VALIDE = 'VALIDE';          // Accepté par l'admin, visible par tous
    case REFUSE = 'REFUSE';          // Refusé par l'admin
    case OUVERT = 'OUVERT';          // Statut historique cree par l'application Java
    case ANNULE = 'ANNULE';          // Statut historique de rejet cote Java
}
