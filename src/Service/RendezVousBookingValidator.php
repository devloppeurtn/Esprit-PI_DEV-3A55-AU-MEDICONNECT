<?php

namespace App\Service;

use App\Entity\Medecin;

class RendezVousBookingValidator
{
    public function __construct(
        private DisponibiliteService $disponibiliteService
    ) {
    }

    /**
     * @return array{ok:bool, message:string, endAt:\DateTimeInterface|null}
     */
    public function validateRequestedSlot(Medecin $medecin, \DateTimeInterface $requestedStart): array
    {
        $planning = $medecin->getPlanning();
        if ($planning === null) {
            return [
                'ok' => false,
                'message' => 'Demande refusee: planning du medecin non configure.',
                'endAt' => null,
            ];
        }

        $grid = $this->disponibiliteService->getAgendaGrid($medecin, $requestedStart, $planning);
        if ($grid === []) {
            return [
                'ok' => false,
                'message' => 'Demande refusee: jour non travaille ou hors horaires du medecin.',
                'endAt' => null,
            ];
        }

        foreach ($grid as $slot) {
            $slotStart = $slot['de'] ?? null;
            $slotEnd = $slot['a'] ?? null;
            if (!$slotStart instanceof \DateTimeInterface || !$slotEnd instanceof \DateTimeInterface) {
                continue;
            }

            // Accepte si la demande est dans un creneau theorique, pas seulement pile sur le debut.
            if ($requestedStart >= $slotStart && $requestedStart < $slotEnd) {
                if (($slot['type'] ?? '') === 'libre') {
                    return [
                        'ok' => true,
                        'message' => '',
                        'endAt' => $slotEnd,
                    ];
                }

                return [
                    'ok' => false,
                    'message' => 'Demande refusee: medecin deja occupe sur ce creneau.',
                    'endAt' => null,
                ];
            }
        }

        return [
            'ok' => false,
            'message' => 'Demande refusee: horaire hors planning du medecin.',
            'endAt' => null,
        ];
    }
}
