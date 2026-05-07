<?php

namespace App\Service;

final class RendezVousRulesService
{
    public function isEndAfterStart(\DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        return $end > $start;
    }

    public function isWithinWorkingHours(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        int $startHour,
        int $endHour
    ): bool {
        if ($startHour < 0 || $startHour > 23 || $endHour < 0 || $endHour > 23) {
            return false;
        }

        if ($end <= $start) {
            return false;
        }

        $startMinutes = ((int) $start->format('H')) * 60 + (int) $start->format('i');
        $endMinutes = ((int) $end->format('H')) * 60 + (int) $end->format('i');
        $workStart = $startHour * 60;
        $workEnd = $endHour * 60;

        return $startMinutes >= $workStart && $endMinutes <= $workEnd;
    }
}
