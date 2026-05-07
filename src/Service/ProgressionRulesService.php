<?php

namespace App\Service;

final class ProgressionRulesService
{
    public function isScoreNonNegative(int $score): bool
    {
        return $score >= 0;
    }

    public function shouldAwardBadge(int $score, int $threshold): bool
    {
        if ($threshold <= 0) {
            return false;
        }

        return $score >= $threshold;
    }
}
