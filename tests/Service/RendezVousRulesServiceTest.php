<?php

namespace App\Tests\Service;

use App\Service\RendezVousRulesService;
use PHPUnit\Framework\TestCase;

class RendezVousRulesServiceTest extends TestCase
{
    public function testEndAfterStart(): void
    {
        $service = new RendezVousRulesService();
        $start = new \DateTimeImmutable('2026-03-04 10:00');
        $end = new \DateTimeImmutable('2026-03-04 10:30');

        self::assertTrue($service->isEndAfterStart($start, $end));
    }

    public function testWithinWorkingHours(): void
    {
        $service = new RendezVousRulesService();
        $start = new \DateTimeImmutable('2026-03-04 09:00');
        $end = new \DateTimeImmutable('2026-03-04 10:00');

        self::assertTrue($service->isWithinWorkingHours($start, $end, 8, 17));
        self::assertFalse($service->isWithinWorkingHours($start, $end, 10, 17));
    }
}
