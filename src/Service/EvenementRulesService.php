<?php

namespace App\Service;

final class EvenementRulesService
{
    public function isEventDateNotPast(\DateTimeInterface $eventDate, ?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable('today');
        return $eventDate >= $now;
    }

    public function canAcceptParticipants(int $currentParticipants, ?int $maxParticipants): bool
    {
        if ($currentParticipants < 0) {
            return false;
        }

        if ($maxParticipants === null) {
            return true;
        }

        if ($maxParticipants < 1) {
            return false;
        }

        return $currentParticipants < $maxParticipants;
    }
}
