<?php

namespace App\Command;

use App\Enum\StatutCommande;
use App\Repository\LigneCommandeRepository;
use App\Repository\ProduitRepository;
use App\Service\AiStockForecastModelService;
use App\Service\PhpStockModelTrainerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:stock:train-forecast-model',
    description: 'Train a real ML model for next-7-day stock demand forecasting.',
)]
class TrainStockForecastModelCommand extends Command
{
    private const DEFAULT_LOOKBACK_DAYS = 180;
    private const DEFAULT_MIN_ROWS = 120;

    public function __construct(
        private LigneCommandeRepository $ligneCommandeRepository,
        private ProduitRepository $produitRepository,
        private AiStockForecastModelService $aiStockForecastModelService,
        private PhpStockModelTrainerService $phpStockModelTrainerService,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('lookback-days', null, InputOption::VALUE_REQUIRED, 'Training history depth in days', (string) self::DEFAULT_LOOKBACK_DAYS)
            ->addOption('min-rows', null, InputOption::VALUE_REQUIRED, 'Minimum training rows required', (string) self::DEFAULT_MIN_ROWS)
            ->addOption('python-bin', null, InputOption::VALUE_REQUIRED, 'Python binary path', 'python');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $lookbackDays = max(45, (int) $input->getOption('lookback-days'));
        $minRows = max(50, (int) $input->getOption('min-rows'));
        $pythonBin = trim((string) $input->getOption('python-bin'));

        $asOf = (new \DateTimeImmutable('now'))->setTime(23, 59, 59);
        $from = $asOf->modify('-' . $lookbackDays . ' days')->setTime(0, 0, 0);

        $rawDailySales = $this->ligneCommandeRepository->findDailySalesByProduct(
            $from,
            $asOf,
            [
                StatutCommande::VALIDEE->value,
                StatutCommande::PREPAREE->value,
                StatutCommande::LIVREE->value,
            ]
        );

        $dailySalesMap = $this->buildDailySalesMap($rawDailySales);
        $dateKeys = $this->buildDateKeys($from, $asOf);
        $products = $this->produitRepository->findAll();
        $rows = $this->buildTrainingRows($products, $dailySalesMap, $dateKeys);

        if (count($rows) < $minRows) {
            $io->error(sprintf(
                'Not enough training rows (%d). Need at least %d. Add more order history or increase lookback.',
                count($rows),
                $minRows
            ));

            return Command::FAILURE;
        }

        $projectDir = rtrim((string) $this->parameterBag->get('kernel.project_dir'), '/\\');
        $mlDir = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'ml';
        if (!is_dir($mlDir) && !mkdir($mlDir, 0777, true) && !is_dir($mlDir)) {
            $io->error('Unable to create var/ml directory.');
            return Command::FAILURE;
        }

        $datasetPath = $mlDir . DIRECTORY_SEPARATOR . 'stock_training_rows.json';
        $modelPath = $this->aiStockForecastModelService->getModelPath();
        $scriptPath = $projectDir . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'train_stock_model.py';

        file_put_contents($datasetPath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $io->text(sprintf('Rows prepared: %d', count($rows)));
        $io->text(sprintf('Dataset: %s', $datasetPath));
        $io->text(sprintf('Model output: %s', $modelPath));

        $trained = false;
        if (is_file($scriptPath) && $pythonBin !== '') {
            $pythonCheck = new Process([$pythonBin, '--version']);
            $pythonCheck->setTimeout(10);
            $pythonCheck->run();

            if ($pythonCheck->isSuccessful()) {
                $process = new Process([
                    $pythonBin,
                    $scriptPath,
                    '--input',
                    $datasetPath,
                    '--output',
                    $modelPath,
                ]);
                $process->setTimeout(180);
                $process->run();

                if ($process->isSuccessful()) {
                    $stdout = trim($process->getOutput());
                    if ($stdout !== '') {
                        $io->writeln($stdout);
                    }
                    $trained = true;
                    $io->text('Trainer used: python');
                } else {
                    $io->warning('Python training failed, switching to PHP trainer.');
                    $io->writeln($process->getErrorOutput() !== '' ? $process->getErrorOutput() : $process->getOutput());
                }
            } else {
                $io->warning('Python not available, switching to PHP trainer.');
            }
        } else {
            $io->warning('Python script not found, switching to PHP trainer.');
        }

        if (!$trained) {
            $model = $this->phpStockModelTrainerService->trainAndSave($rows, $modelPath);
            $metrics = $model['metrics'] ?? [];
            $io->text(sprintf(
                'MODEL_TRAINED rows=%d mae_test=%.3f rmse_test=%.3f output=%s',
                (int) ($metrics['rows_total'] ?? 0),
                (float) ($metrics['mae_test'] ?? 0.0),
                (float) ($metrics['rmse_test'] ?? 0.0),
                $modelPath
            ));
            $io->text('Trainer used: php');
        }

        if (!is_file($modelPath)) {
            $io->error('Training finished but model file was not generated.');
            return Command::FAILURE;
        }

        $io->success('AI stock forecast model trained successfully.');

        return Command::SUCCESS;
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
     * @return list<string>
     */
    private function buildDateKeys(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $keys = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $keys[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $keys;
    }

    /**
     * @param array<int, object> $products
     * @param array<int, array<string, int>> $dailySalesMap
     * @param list<string> $dateKeys
     * @return list<array<string, int|float|string>>
     */
    private function buildTrainingRows(array $products, array $dailySalesMap, array $dateKeys): array
    {
        $rows = [];
        $countDates = count($dateKeys);
        if ($countDates < 30) {
            return $rows;
        }

        foreach ($products as $product) {
            if (!method_exists($product, 'getId') || $product->getId() === null) {
                continue;
            }

            $productId = (int) $product->getId();
            $sales = $dailySalesMap[$productId] ?? [];

            for ($i = 14; $i <= $countDates - 7; $i++) {
                $last1 = $this->sumIndexWindow($sales, $dateKeys, $i - 1, $i - 1);
                $last3 = $this->sumIndexWindow($sales, $dateKeys, $i - 3, $i - 1);
                $last7 = $this->sumIndexWindow($sales, $dateKeys, $i - 7, $i - 1);
                $prev7 = $this->sumIndexWindow($sales, $dateKeys, $i - 14, $i - 8);
                $last14 = $this->sumIndexWindow($sales, $dateKeys, $i - 14, $i - 1);
                $target7 = $this->sumIndexWindow($sales, $dateKeys, $i, $i + 6);

                if ($last14 === 0 && $target7 === 0) {
                    continue;
                }

                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateKeys[$i]);
                if (!$date instanceof \DateTimeImmutable) {
                    continue;
                }

                $rows[] = [
                    'last_1d' => $last1,
                    'last_3d' => $last3,
                    'last_7d' => $last7,
                    'prev_7d' => $prev7,
                    'last_14d' => $last14,
                    'trend_7d' => ($last7 + 1.0) / ($prev7 + 1.0),
                    'dow' => (int) $date->format('N'),
                    'month' => (int) $date->format('n'),
                    'target' => $target7,
                    'sample_date' => $date->format('Y-m-d'),
                    'product_id' => $productId,
                ];
            }
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp((string) $a['sample_date'], (string) $b['sample_date'])
        );

        return $rows;
    }

    /**
     * @param array<string, int> $sales
     * @param list<string> $dateKeys
     */
    private function sumIndexWindow(array $sales, array $dateKeys, int $startIndex, int $endIndex): int
    {
        $sum = 0;
        for ($idx = $startIndex; $idx <= $endIndex; $idx++) {
            if (!isset($dateKeys[$idx])) {
                continue;
            }
            $sum += $sales[$dateKeys[$idx]] ?? 0;
        }

        return $sum;
    }
}
