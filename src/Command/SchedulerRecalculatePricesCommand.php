<?php

namespace App\Command;

use App\Repository\ProduitRepository;
use App\Service\ProductPricingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:recalculate-prices',
    description: 'Scheduler job: Recalculate dynamic AI-powered product prices every 4 hours.',
)]
class SchedulerRecalculatePricesCommand extends Command
{
    public function __construct(
        private ProductPricingService $pricingService,
        private ProduitRepository $produits,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Dynamic Price Recalculation (Scheduler)');

        try {
            $io->info('Starting price recalculation cycle...');

            // Get all active products
            $products = $this->produits->findBy(['actif' => true]);

            if (empty($products)) {
                $io->info('No active products found.');
                return Command::SUCCESS;
            }

            $io->section('📊 Processing ' . count($products) . ' Products');

            $progressBar = $io->createProgressBar(count($products));
            $progressBar->start();

            $pricingUpdates = [
                'updated' => 0,
                'unchanged' => 0,
                'errors' => 0,
            ];

            $priceChanges = [];

            foreach ($products as $product) {
                try {
                    // Get previous price
                    $oldPrice = $product->getPrixUnitaire();

                    // Calculate new AI-powered price
                    $newPrice = $this->pricingService->calculateDynamicPrice($product);

                    // Apply price update
                    if ($newPrice !== $oldPrice) {
                        $product->setPrixUnitaire($newPrice);
                        $pricingUpdates['updated']++;

                        $priceChange = [
                            'product_id' => $product->getId(),
                            'product_name' => $product->getNom(),
                            'old_price' => $oldPrice,
                            'new_price' => $newPrice,
                            'difference' => $newPrice - $oldPrice,
                            'percent_change' => round(($newPrice - $oldPrice) / $oldPrice * 100, 2),
                            'stock' => $product->getStock(),
                            'demand' => $this->getEstimatedDemand($product),
                        ];

                        $priceChanges[] = $priceChange;
                    } else {
                        $pricingUpdates['unchanged']++;
                    }
                } catch (\Exception $e) {
                    $pricingUpdates['errors']++;
                    $this->logger->error('Price recalculation failed for product', [
                        'product_id' => $product->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Display summary
            $io->section('📈 Pricing Update Summary');
            $io->table(
                ['Status', 'Count'],
                [
                    ['✅ Updated', $pricingUpdates['updated']],
                    ['⚪ Unchanged', $pricingUpdates['unchanged']],
                    ['❌ Errors', $pricingUpdates['errors']],
                ]
            );

            // Display price changes
            if (!empty($priceChanges)) {
                $io->section('💰 Price Changes Detail');

                // Sort by percent change (largest increases first)
                usort($priceChanges, fn($a, $b) => $b['percent_change'] <=> $a['percent_change']);

                // Show top 10 changes
                $topChanges = array_slice($priceChanges, 0, 10);

                $tableRows = array_map(fn($change) => [
                    $change['product_id'],
                    $change['product_name'],
                    sprintf('€%.2f', $change['old_price']),
                    sprintf('€%.2f', $change['new_price']),
                    sprintf('%+.2f€ (%+.1f%%)', $change['difference'], $change['percent_change']),
                    'Stock: ' . $change['stock'] . ', Demand: ' . $change['demand'],
                ], $topChanges);

                $io->table(
                    ['ID', 'Product', 'Old Price', 'New Price', 'Change', 'Context'],
                    $tableRows
                );

                // Calculate average price change
                $totalChange = array_sum(array_column($priceChanges, 'difference'));
                $avgChange = round($totalChange / count($priceChanges), 2);

                $io->info(sprintf(
                    'Average price change: %+.2f€ across %d products',
                    $avgChange,
                    count($priceChanges)
                ));
            }

            // Persist changes (if using EntityManager)
            // $this->entityManager->flush();

            // Log results
            $this->logger->info('Dynamic price recalculation completed', [
                'updated' => $pricingUpdates['updated'],
                'unchanged' => $pricingUpdates['unchanged'],
                'errors' => $pricingUpdates['errors'],
                'total_products' => count($products),
                'price_changes' => count($priceChanges),
                'execution_time' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success('Price recalculation completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Price recalculation failed: ' . $e->getMessage());

            $this->logger->error('Dynamic price recalculation command failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    private function getEstimatedDemand(object $product): string
    {
        // Placeholder for demand estimation
        // In production, would calculate from historical sales data
        return 'moderate';
    }
}
