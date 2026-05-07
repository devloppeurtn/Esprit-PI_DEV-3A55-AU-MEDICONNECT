<?php

namespace App\Tests\Service;

use App\Service\ProgressionRulesService;
use PHPUnit\Framework\TestCase;

class ProgressionRulesServiceTest extends TestCase
{
    public function testScoreNonNegative(): void
    {
        $service = new ProgressionRulesService();

        self::assertTrue($service->isScoreNonNegative(0));
        self::assertFalse($service->isScoreNonNegative(-3));
    }

    public function testBadgeAwardThreshold(): void
    {
        $service = new ProgressionRulesService();

        self::assertTrue($service->shouldAwardBadge(80, 60));
        self::assertFalse($service->shouldAwardBadge(50, 60));
    }
}
