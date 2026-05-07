<?php

namespace App\Command;

use App\Repository\LigneCommandeRepository;
use App\Repository\ProduitRepository;
use App\Service\PhpStockModelTrainerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:scheduler:train-stock-model',
    description: 'Scheduler job: Train stock demand AI model with latest sales data.',
)]
class SchedulerTrainStockModelCommand extends Command
{
    private const DEFAULT_LOOKBACK_DAYS = 180;
    private const DEFAULT_MIN_ROWS = 120;

    public function __construct(
        private LigneCommandeRepository $ligneCommandeRepository,
        private ProduitRepository $produitRepository,
        private PhpStockModelTrainerService $phpStockModelTrainerService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'lookback-days',
                null,
                InputOption::VALUE_REQUIRED,
                'Training history depth in days',
                (string) self::DEFAULT_LOOKBACK_DAYS
            )
            ->addOption(
                'min-rows',
                null,
                InputOption::VALUE_REQUIRED,
                'Minimum training rows required',
                (string) self::DEFAULT_MIN_ROWS
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startTime = microtime(true);

        $lookbackDays = max(45, (int) $input->getOption('lookback-days'));
        $minRows = max(50, (int) $input->getOption('min-rows'));

        $io->title('AI Stock Demand Model Trainer (Scheduler)');
        $io->section('Fetching training data...');

        $asOf = (new \DateTimeImmutable('now'))->setTime(23, 59, 59);
        $from = $asOf->modify('-' . $lookbackDays . ' days')->setTime(0, 0, 0);

        try {
            $rawDailySales = $this->ligneCommandeRepository->findDailySalesByProduct(
                $from,
                $asOf,
                [
                    \App\Enum\StatutCommande::VALIDEE->value,
                    \App\Enum\StatutCommande::PREPAREE->value,
                    \App\Enum\StatutCommande::LIVREE->value,
                ]
            );

            $io->info(sprintf('Raw sales records: %d', count($rawDailySales)));

            // Build training dataset
            $products = $this->produitRepository->findAll();
            $rows = [];

            foreach ($products as $product) {
                foreach ($rawDailySales as $sale) {
                    if ((int)$sale['product_id'] === $product->getId()) {
                        $rows[] = [
                            'product_id' => $product->getId(),
                            'date' => $sale['sale_date'],
                            'quantity' => (int)$sale['qty'],
                        ];
                    }
                }
            }

            $io->info(sprintf('Training rows prepared: %d', count($rows)));

            if (count($rows) < $minRows) {
                $io->warning(sprintf(
                    'Not enough training data: %d rows (minimum: %d)',
                    count($rows),
                    $minRows
                ));

                $this->logger->warning('AI Model Training: Insufficient data', [
                    'rows_count' => count($rows),
                    'min_required' => $minRows,
                    'lookback_days' => $lookbackDays,
                ]);

                return Command::FAILURE;
            }

            // Train the model
            $io->section('Training ML model...');
            $metrics = $this->phpStockModelTrainerService->trainModel($rows);

            $io->success('Model training completed successfully!');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Rows', $metrics['rows_total'] ?? 0],
                    ['Training Rows', $metrics['rows_train'] ?? 0],
                    ['Test Rows', $metrics['rows_test'] ?? 0],
                    ['MAE (Test)', round($metrics['mae_test'] ?? 0, 4)],
                    ['RMSE (Test)', round($metrics['rmse_test'] ?? 0, 4)],
                ]
            );

            $duration = round(microtime(true) - $startTime, 2);
            
            // Log success
            $this->logger->info('AI Model Training: Successfully completed', [
                'rows_processed' => count($rows),
                'duration_seconds' => $duration,
                'metrics' => $metrics,
                'lookback_days' => $lookbackDays,
            ]);

            $io->newLine();
            $io->info(sprintf('⏱ Training completed in %s seconds', $duration));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Model training failed: ' . $e->getMessage());

            $this->logger->error('AI Model Training: Failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }
}
