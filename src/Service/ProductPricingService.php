<?php

namespace App\Service;

use App\Entity\Produit;
use App\Enum\StatutCommande;
use App\Repository\LigneCommandeRepository;

class ProductPricingService
{
    private const LOOKBACK_DAYS = 15;
    private const LOW_STOCK_THRESHOLD = 5;
    private const HIGH_STOCK_THRESHOLD = 50;
    private const LOW_STOCK_MULTIPLIER = 1.10;  // +10%
    private const HIGH_STOCK_MULTIPLIER = 0.95; // -5%
    private const AI_BASE_PRESSURE = 0.65;
    private const AI_PRESSURE_SENSITIVITY = 0.18;
    private const MIN_MULTIPLIER = 0.85;
    private const MAX_MULTIPLIER = 1.20;

    /** @var array<int, array<string, int>>|null */
    private ?array $dailySalesMapCache = null;
    private ?string $dailySalesCacheDay = null;

    public function __construct(
        private LigneCommandeRepository $ligneCommandeRepository,
        private AiStockForecastModelService $aiStockForecastModelService
    ) {
    }

    public function calculateForProduct(Produit $produit): array
    {
        $basePrice = (float) $produit->getPrix();
        $stock = max(0, (int) ($produit->getStock() ?? 0));

        $ruleMultiplier = $this->getRuleMultiplier($stock);
        $multiplier = $ruleMultiplier;
        $source = 'rules';
        $aiDemand7d = null;
        $demandPressure = null;

        if ($produit->getId() !== null) {
            $asOf = (new \DateTimeImmutable('now'))->setTime(23, 59, 59);
            $features = $this->buildModelFeatures($produit, $asOf);
            $aiPrediction = $this->aiStockForecastModelService->predictNext7Days($features);

            if ($aiPrediction !== null) {
                $aiDemand7d = round($aiPrediction, 2);
                $demandPressure = $stock > 0 ? ($aiPrediction / max(1, $stock)) : ($aiPrediction > 0 ? 2.0 : 0.0);
                $aiMultiplier = $this->computeAiMultiplier($demandPressure, $stock);

                // Blend rule + AI so prices stay stable even on sparse datasets.
                $multiplier = (($aiMultiplier * 0.75) + ($ruleMultiplier * 0.25));
                $source = 'ai_real';
            }
        }

        // Business guardrail: when stock is very low, never discount.
        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            $multiplier = max($multiplier, self::LOW_STOCK_MULTIPLIER);
        }

        $multiplier = max(self::MIN_MULTIPLIER, min(self::MAX_MULTIPLIER, $multiplier));
        $finalPrice = round($basePrice * $multiplier, 2);

        return [
            'base' => $basePrice,
            'final' => $finalPrice,
            'adjusted' => abs($finalPrice - $basePrice) > 0.0001,
            'label' => $source === 'ai_real'
                ? $this->getAiLabel($multiplier, $demandPressure)
                : $this->getRuleLabel($multiplier),
            'source' => $source,
            'multiplier' => round($multiplier, 4),
            'delta' => round($finalPrice - $basePrice, 2),
            'ai_demand_7d' => $aiDemand7d,
            'demand_pressure' => $demandPressure !== null ? round($demandPressure, 3) : null,
        ];
    }

    public function getFinalPrice(Produit $produit): float
    {
        return $this->calculateForProduct($produit)['final'];
    }

    /**
     * @param Produit[] $produits
     * @return array<int, array<string, mixed>>
     */
    public function calculateForProducts(array $produits): array
    {
        $result = [];
        foreach ($produits as $produit) {
            if (!$produit instanceof Produit || $produit->getId() === null) {
                continue;
            }

            $result[$produit->getId()] = $this->calculateForProduct($produit);
        }

        return $result;
    }

    public function getFinalPriceAsString(Produit $produit): string
    {
        return number_format($this->getFinalPrice($produit), 2, '.', '');
    }

    private function getRuleMultiplier(int $stock): float
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

    private function getAiLabel(float $multiplier, ?float $demandPressure): ?string
    {
        $pct = (int) round(abs(($multiplier - 1.0) * 100));
        $pressure = $demandPressure !== null ? number_format($demandPressure, 2, '.', '') : null;

        if ($multiplier > 1.0) {
            return 'Modele IA reel: +' . $pct . '% (pression=' . ($pressure ?? '?') . ')';
        }

        if ($multiplier < 1.0) {
            return 'Modele IA reel: -' . $pct . '% (pression=' . ($pressure ?? '?') . ')';
        }

        return 'Modele IA reel: stable';
    }

    private function computeAiMultiplier(float $demandPressure, int $stock): float
    {
        $stockBias = 0.0;
        if ($stock <= 2) {
            $stockBias = 0.04;
        } elseif ($stock >= 80) {
            $stockBias = -0.03;
        }

        $delta = (($demandPressure - self::AI_BASE_PRESSURE) * self::AI_PRESSURE_SENSITIVITY) + $stockBias;

        return 1.0 + $delta;
    }

    private function getDailySalesMap(\DateTimeImmutable $asOf): array
    {
        $dayKey = $asOf->format('Y-m-d');
        if ($this->dailySalesMapCache !== null && $this->dailySalesCacheDay === $dayKey) {
            return $this->dailySalesMapCache;
        }

        $from = $asOf->modify('-' . self::LOOKBACK_DAYS . ' days')->setTime(0, 0, 0);
        $rows = $this->ligneCommandeRepository->findDailySalesByProduct(
            $from,
            $asOf,
            $this->getForecastStatuses()
        );

        $map = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $saleDate = (string) ($row['sale_date'] ?? '');
            $qty = (int) round((float) ($row['qty'] ?? 0));

            if ($productId <= 0 || $saleDate === '') {
                continue;
            }

            if (!isset($map[$productId])) {
                $map[$productId] = [];
            }

            $map[$productId][$saleDate] = ($map[$productId][$saleDate] ?? 0) + $qty;
        }

        $this->dailySalesMapCache = $map;
        $this->dailySalesCacheDay = $dayKey;

        return $map;
    }

    /**
     * @param array<string, int> $dailySales
     */
    private function sumWindow(
        array $dailySales,
        \DateTimeImmutable $asOf,
        int $startDaysAgo,
        int $endDaysAgo
    ): int {
        $sum = 0;
        for ($d = $startDaysAgo; $d <= $endDaysAgo; $d++) {
            $day = $asOf->modify('-' . $d . ' days')->format('Y-m-d');
            $sum += $dailySales[$day] ?? 0;
        }

        return $sum;
    }

    /**
     * @return array<string, float|int>
     */
    private function buildModelFeatures(Produit $produit, \DateTimeImmutable $asOf): array
    {
        $productId = (int) $produit->getId();
        $dailySales = $this->getDailySalesMap($asOf)[$productId] ?? [];

        $last1d = $this->sumWindow($dailySales, $asOf, 1, 1);
        $last3d = $this->sumWindow($dailySales, $asOf, 1, 3);
        $last7d = $this->sumWindow($dailySales, $asOf, 1, 7);
        $prev7d = $this->sumWindow($dailySales, $asOf, 8, 14);
        $last14d = $this->sumWindow($dailySales, $asOf, 1, 14);

        return [
            'last_1d' => $last1d,
            'last_3d' => $last3d,
            'last_7d' => $last7d,
            'prev_7d' => $prev7d,
            'last_14d' => $last14d,
            'trend_7d' => ($last7d + 1.0) / ($prev7d + 1.0),
            'dow' => (int) $asOf->format('N'),
            'month' => (int) $asOf->format('n'),
        ];
    }

    /**
     * @return list<string>
     */
    private function getForecastStatuses(): array
    {
        return [
            StatutCommande::VALIDEE->value,
            StatutCommande::PREPAREE->value,
            StatutCommande::LIVREE->value,
        ];
    }
}
