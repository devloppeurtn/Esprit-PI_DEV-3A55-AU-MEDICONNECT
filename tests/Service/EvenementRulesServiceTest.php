<?php

namespace App\Tests\Service;

use App\Service\EvenementRulesService;
use PHPUnit\Framework\TestCase;

class EvenementRulesServiceTest extends TestCase
{
    public function testEventDateNotPast(): void
    {
        $service = new EvenementRulesService();
        $now = new \DateTimeImmutable('2026-03-04');

        self::assertTrue($service->isEventDateNotPast(new \DateTimeImmutable('2026-03-04'), $now));
        self::assertFalse($service->isEventDateNotPast(new \DateTimeImmutable('2026-03-03'), $now));
    }

    public function testCanAcceptParticipantsRespectsMax(): void
    {
        $service = new EvenementRulesService();

        self::assertTrue($service->canAcceptParticipants(5, 10));
        self::assertFalse($service->canAcceptParticipants(10, 10));
        self::assertTrue($service->canAcceptParticipants(10, null));
    }
}
