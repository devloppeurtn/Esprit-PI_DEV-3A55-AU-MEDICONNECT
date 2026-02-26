<?php

namespace App\Service;

use App\Entity\Produit;
use App\Enum\StatutCommande;
use App\Repository\LigneCommandeRepository;
use App\Repository\ProduitRepository;

class StockDemandForecastService
{
    private const LOOKBACK_DAYS = 15;
    private const RECENT_DAYS = 7;
    private const TREND_DAYS = 7;
    private const FORECAST_DAYS = 7;
    private const MIN_TREND = 0.75;
    private const MAX_TREND = 1.50;

    public function __construct(
        private LigneCommandeRepository $ligneCommandeRepository,
        private ProduitRepository $produitRepository,
        private AiStockForecastModelService $aiStockForecastModelService
    ) {
    }

    /**
     * @param Produit[] $produits
     * @return array<int, array{
     *   product: Produit,
     *   stock: int,
     *   sold_last_7d: int,
     *   sold_previous_21d: int,
     *   avg_daily_recent: float,
     *   trend_factor: float,
     *   forecast_next_7d: int,
     *   forecast_source: string,
     *   safety_stock: int,
     *   days_until_stockout: ?int,
     *   should_alert: bool,
     *   alert_level: string
     * }>
     */
    public function forecastForProducts(array $produits, ?\DateTimeImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? new \DateTimeImmutable('now'))->setTime(23, 59, 59);
        $from = $asOf->modify('-' . self::LOOKBACK_DAYS . ' days')->setTime(0, 0, 0);

        $rawDailySales = $this->ligneCommandeRepository->findDailySalesByProduct(
            $from,
            $asOf,
            $this->getForecastStatuses()
        );

        $salesByProductAndDate = $this->buildDailySalesMap($rawDailySales);
        $results = [];

        foreach ($produits as $produit) {
            if (!$produit instanceof Produit || $produit->getId() === null) {
                continue;
            }

            $productId = $produit->getId();
            $results[$productId] = $this->computeForecast(
                $produit,
                $salesByProductAndDate[$productId] ?? [],
                $asOf
            );
        }

        return $results;
    }

    /**
     * @return array<int, array{
     *   product: Produit,
     *   stock: int,
     *   sold_last_7d: int,
     *   sold_previous_21d: int,
     *   avg_daily_recent: float,
     *   trend_factor: float,
     *   forecast_next_7d: int,
     *   forecast_source: string,
     *   safety_stock: int,
     *   days_until_stockout: ?int,
     *   should_alert: bool,
     *   alert_level: string
     * }>
     */
    public function forecastAllProducts(?\DateTimeImmutable $asOf = null): array
    {
        return $this->forecastForProducts($this->produitRepository->findAll(), $asOf);
    }

    /**
     * @return array<int, array{
     *   product: Produit,
     *   stock: int,
     *   sold_last_7d: int,
     *   sold_previous_21d: int,
     *   avg_daily_recent: float,
     *   trend_factor: float,
     *   forecast_next_7d: int,
     *   forecast_source: string,
     *   safety_stock: int,
     *   days_until_stockout: ?int,
     *   should_alert: bool,
     *   alert_level: string
     * }>
     */
    public function getLowStockAlerts(?\DateTimeImmutable $asOf = null): array
    {
        $forecasts = $this->forecastAllProducts($asOf);
        $alerts = array_filter(
            $forecasts,
            static fn (array $item): bool => $item['should_alert'] === true
        );

        uasort(
            $alerts,
            static function (array $a, array $b): int {
                $aDays = $a['days_until_stockout'] ?? PHP_INT_MAX;
                $bDays = $b['days_until_stockout'] ?? PHP_INT_MAX;

                return $aDays <=> $bDays;
            }
        );

        return $alerts;
    }

    /**
     * @param array<int, array{product_id:int|string, sale_date:string, qty:int|string|float}> $rows
     * @return array<int, array<string, int>>
     */
    private function buildDailySalesMap(array $rows): array
    {
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

        return $map;
    }

    /**
     * @param array<string, int> $dailySales
     * @return array{
     *   product: Produit,
     *   stock: int,
     *   sold_last_7d: int,
     *   sold_previous_21d: int,
     *   avg_daily_recent: float,
     *   trend_factor: float,
     *   forecast_next_7d: int,
     *   forecast_source: string,
     *   safety_stock: int,
     *   days_until_stockout: ?int,
     *   should_alert: bool,
     *   alert_level: string
     * }
     */
    private function computeForecast(Produit $produit, array $dailySales, \DateTimeImmutable $asOf): array
    {
        $soldLast7d = $this->sumWindow($dailySales, $asOf, 1, self::RECENT_DAYS);
        $soldPrevious21d = $this->sumWindow(
            $dailySales,
            $asOf,
            self::RECENT_DAYS + 1,
            self::RECENT_DAYS + self::TREND_DAYS
        );

        $avgDailyRecent = $soldLast7d / self::RECENT_DAYS;
        $avgDailyPrevious = $soldPrevious21d / self::TREND_DAYS;

        if ($avgDailyRecent <= 0.0 && $avgDailyPrevious > 0.0) {
            $avgDailyRecent = $avgDailyPrevious;
        }

        $trendFactor = 1.0;
        if ($avgDailyPrevious > 0.0) {
            $trendFactor = $avgDailyRecent / $avgDailyPrevious;
            $trendFactor = max(self::MIN_TREND, min(self::MAX_TREND, $trendFactor));
        } elseif ($avgDailyRecent > 0.0) {
            $trendFactor = 1.10;
        }

        $forecastNext7d = (int) ceil($avgDailyRecent * $trendFactor * self::FORECAST_DAYS);
        if ($forecastNext7d < 0) {
            $forecastNext7d = 0;
        }
        $forecastSource = 'rules';

        $aiPrediction = $this->aiStockForecastModelService->predictNext7Days(
            $this->buildModelFeatures($dailySales, $asOf)
        );

        if ($aiPrediction !== null) {
            $aiForecast = max(0, (int) round($aiPrediction));

            // Guardrail to avoid unrealistic spikes on sparse datasets.
            $maxAllowed = max(10, ($forecastNext7d * 5) + 20);
            $forecastNext7d = min($aiForecast, $maxAllowed);
            $forecastSource = 'ai';
        }

        $safetyStock = max(2, (int) ceil($avgDailyRecent * 3));
        $stock = max(0, (int) ($produit->getStock() ?? 0));

        $daysUntilStockout = null;
        if ($avgDailyRecent > 0.0) {
            $daysUntilStockout = (int) floor($stock / $avgDailyRecent);
        }

        $shouldAlert = $forecastNext7d > 0 && ($stock <= $forecastNext7d || $stock <= $safetyStock);
        $alertLevel = 'ok';
        if ($shouldAlert) {
            $alertLevel = ($daysUntilStockout !== null && $daysUntilStockout <= 3) ? 'critical' : 'warning';
        }

        return [
            'product' => $produit,
            'stock' => $stock,
            'sold_last_7d' => $soldLast7d,
            'sold_previous_21d' => $soldPrevious21d,
            'avg_daily_recent' => round($avgDailyRecent, 2),
            'trend_factor' => round($trendFactor, 2),
            'forecast_next_7d' => $forecastNext7d,
            'forecast_source' => $forecastSource,
            'safety_stock' => $safetyStock,
            'days_until_stockout' => $daysUntilStockout,
            'should_alert' => $shouldAlert,
            'alert_level' => $alertLevel,
        ];
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

    /**
     * @param array<string, int> $dailySales
     * @return array<string, float|int>
     */
    private function buildModelFeatures(array $dailySales, \DateTimeImmutable $asOf): array
    {
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
}
