<?php

namespace App\Command;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Service\StockDemandForecastService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:scheduler:stock-rupture-alerts',
    description: 'Scheduler job: Daily stock rupture alerts and low-stock forecasts.',
)]
class SchedulerStockRuptureAlertsCommand extends Command
{
    private const CRITICAL_DAYS_THRESHOLD = 3;    // Stock will run out in < 3 days
    private const WARNING_DAYS_THRESHOLD = 7;     // Stock will run out in < 7 days
    private const ALERT_STOCK_LEVEL = 10;         // Current stock < 10 units

    public function __construct(
        private ProduitRepository $produitRepository,
        private StockDemandForecastService $stockForecastService,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Daily Stock Rupture Alerts (Scheduler)');

        try {
            $io->section('Analyzing stock levels and forecasts...');

            $allProducts = $this->produitRepository->findAll();
            $criticalProducts = [];
            $warningProducts = [];
            $lowStockProducts = [];

            // Forecast for all products
            $forecasts = $this->stockForecastService->forecastAllProducts();

            foreach ($allProducts as $product) {
                $forecast = $forecasts[$product->getId()] ?? null;
                if (!$forecast) {
                    continue;
                }

                $daysUntilStockout = $forecast['days_until_stockout'];
                $stock = $product->getStock();

                // Categorize by alert level
                if ($daysUntilStockout !== null && $daysUntilStockout <= self::CRITICAL_DAYS_THRESHOLD) {
                    $criticalProducts[] = [
                        'product' => $product,
                        'forecast' => $forecast,
                    ];
                } elseif ($daysUntilStockout !== null && $daysUntilStockout <= self::WARNING_DAYS_THRESHOLD) {
                    $warningProducts[] = [
                        'product' => $product,
                        'forecast' => $forecast,
                    ];
                } elseif ($stock <= self::ALERT_STOCK_LEVEL) {
                    $lowStockProducts[] = [
                        'product' => $product,
                        'forecast' => $forecast,
                    ];
                }
            }

            // Display summary
            $io->section('Alert Summary');
            $io->table(
                ['Alert Level', 'Count'],
                [
                    ['🔴 CRITICAL (< 3 days)', count($criticalProducts)],
                    ['🟠 WARNING (< 7 days)', count($warningProducts)],
                    ['🟡 LOW STOCK (< 10 units)', count($lowStockProducts)],
                ]
            );

            // Display critical products
            if (!empty($criticalProducts)) {
                $io->section('🔴 CRITICAL PRODUCTS - Immediate Reorder Required');
                foreach ($criticalProducts as $item) {
                    $product = $item['product'];
                    $forecast = $item['forecast'];
                    $io->writeln(sprintf(
                        '  • %s | Stock: %d units | Days until empty: %s | Predicted sales: %d units/week',
                        $product->getNom(),
                        $product->getStock(),
                        $forecast['days_until_stockout'] ?? 'N/A',
                        $forecast['forecast_next_7d'] ?? 0,
                    ));
                }
                $this->sendAlertEmail('CRITICAL', $criticalProducts, $output);
            }

            // Display warning products
            if (!empty($warningProducts)) {
                $io->section('🟠 WARNING PRODUCTS - Monitor Closely');
                foreach ($warningProducts as $item) {
                    $product = $item['product'];
                    $forecast = $item['forecast'];
                    $io->writeln(sprintf(
                        '  • %s | Stock: %d units | Days available: %s | Predicted sales: %d units/week',
                        $product->getNom(),
                        $product->getStock(),
                        $forecast['days_until_stockout'] ?? 'N/A',
                        $forecast['forecast_next_7d'] ?? 0,
                    ));
                }
                $this->sendAlertEmail('WARNING', $warningProducts, $output);
            }

            // Log results
            $this->logger->info('Stock rupture alerts generated', [
                'critical_count' => count($criticalProducts),
                'warning_count' => count($warningProducts),
                'low_stock_count' => count($lowStockProducts),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success(sprintf(
                'Stock analysis complete: %d critical, %d warning, %d low-stock products',
                count($criticalProducts),
                count($warningProducts),
                count($lowStockProducts)
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to generate stock alerts: ' . $e->getMessage());

            $this->logger->error('Stock rupture alerts failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Send alert email to management.
     */
    private function sendAlertEmail(string $level, array $products, OutputInterface $output): void
    {
        try {
            $subject = match ($level) {
                'CRITICAL' => '🔴 CRITICAL Stock Alert - Immediate Action Required',
                'WARNING' => '🟠 WARNING Stock Alert - Monitor Closely',
                default => 'Stock Alert',
            };

            $body = $this->buildEmailBody($level, $products);

            $email = (new Email())
                ->from('alerts@mediconnect.local')
                ->to('stock-manager@mediconnect.local')
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);

            $this->logger->info('Stock alert email sent', [
                'level' => $level,
                'product_count' => count($products),
            ]);

        } catch (\Exception $e) {
            $this->logger->warning('Failed to send stock alert email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build HTML email body.
     */
    private function buildEmailBody(string $level, array $products): string
    {
        $rows = '';
        foreach ($products as $item) {
            $product = $item['product'];
            $forecast = $item['forecast'];
            $rows .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%s</td><td>%d</td></tr>',
                htmlspecialchars($product->getNom()),
                $product->getStock(),
                $forecast['days_until_stockout'] ?? 'N/A',
                $forecast['forecast_next_7d'] ?? 0
            );
        }

        return <<<HTML
<h2>$level Stock Alert</h2>
<p>The following products require immediate attention:</p>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Product</th>
            <th>Current Stock</th>
            <th>Days Until Empty</th>
            <th>Predicted Sales (7d)</th>
        </tr>
    </thead>
    <tbody>
        $rows
    </tbody>
</table>
<p>Generated: {$this->getCurrentDate()}</p>
HTML;
    }

    private function getCurrentDate(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }
}
