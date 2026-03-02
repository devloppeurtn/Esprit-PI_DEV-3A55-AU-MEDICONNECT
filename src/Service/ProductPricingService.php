<?php

namespace App\Service;

use App\Entity\Produit;

class ProductPricingService
{
    private const LOW_STOCK_THRESHOLD = 5;
    private const HIGH_STOCK_THRESHOLD = 50;
    private const LOW_STOCK_MULTIPLIER = 1.10;  // +10%
    private const HIGH_STOCK_MULTIPLIER = 0.95; // -5%

    public function calculateForProduct(Produit $produit): array
    {
        $basePrice = (float) $produit->getPrix();
        $multiplier = $this->getMultiplier($produit->getStock() ?? 0);
        $finalPrice = round($basePrice * $multiplier, 2);

        return [
            'base' => $basePrice,
            'final' => $finalPrice,
            'adjusted' => abs($finalPrice - $basePrice) > 0.0001,
            'label' => $this->getRuleLabel($multiplier),
        ];
    }

    public function getFinalPrice(Produit $produit): float
    {
        return $this->calculateForProduct($produit)['final'];
    }

    public function getFinalPriceAsString(Produit $produit): string
    {
        return number_format($this->getFinalPrice($produit), 2, '.', '');
    }

    private function getMultiplier(int $stock): float
    {
        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            return self::LOW_STOCK_MULTIPLIER;
        }

        if ($stock >= self::HIGH_STOCK_THRESHOLD) {
            return self::HIGH_STOCK_MULTIPLIER;
        }

        return 1.0;
    }

    private function getRuleLabel(float $multiplier): ?string
    {
        if ($multiplier > 1.0) {
            return 'Prix dynamique: +10% (stock faible)';
        }

        if ($multiplier < 1.0) {
            return 'Prix dynamique: -5% (stock eleve)';
        }

        return null;
    }
}

