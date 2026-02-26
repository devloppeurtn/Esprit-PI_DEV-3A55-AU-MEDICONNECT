<?php

namespace App\Command;

use App\Service\StockDemandForecastService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stock:forecast-alerts',
    description: 'Predict next-week product demand from order history and display low-stock alerts.',
)]
class StockForecastAlertsCommand extends Command
{
    public function __construct(private StockDemandForecastService $stockDemandForecastService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $alerts = $this->stockDemandForecastService->getLowStockAlerts();

        if ($alerts === []) {
            $io->success('No predicted stockout risk for the next 7 days.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($alerts as $item) {
            $rows[] = [
                $item['product']->getId(),
                $item['product']->getNom(),
                $item['stock'],
                $item['forecast_next_7d'],
                $item['days_until_stockout'] ?? '-',
                strtoupper((string) ($item['forecast_source'] ?? 'rules')),
                strtoupper($item['alert_level']),
            ];
        }

        $io->warning(sprintf('%d product(s) at risk detected.', count($rows)));
        $io->table(
            ['ID', 'Product', 'Stock', 'Forecast 7d', 'Stockout (days)', 'Source', 'Level'],
            $rows
        );

        return Command::SUCCESS;
    }
}
