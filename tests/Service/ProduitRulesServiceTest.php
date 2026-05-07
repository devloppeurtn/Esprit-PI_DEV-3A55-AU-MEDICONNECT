<?php

namespace App\Tests\Service;

use App\Service\ProduitRulesService;
use PHPUnit\Framework\TestCase;

class ProduitRulesServiceTest extends TestCase
{
    public function testPriceMustBePositive(): void
    {
        $service = new ProduitRulesService();

        self::assertFalse($service->isPricePositive(0));
        self::assertFalse($service->isPricePositive(-10));
        self::assertTrue($service->isPricePositive(19.99));
    }

    public function testStockCannotBeNegative(): void
    {
        $service = new ProduitRulesService();

        self::assertFalse($service->isStockNonNegative(-1));
        self::assertTrue($service->isStockNonNegative(0));
    }
}
