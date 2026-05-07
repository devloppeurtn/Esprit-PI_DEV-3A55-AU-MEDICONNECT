<?php

namespace App\Service;

final class ProduitRulesService
{
    public function isPricePositive(string|float|int $price): bool
    {
        return (float) $price > 0;
    }

    public function isStockNonNegative(int $stock): bool
    {
        return $stock >= 0;
    }
}
