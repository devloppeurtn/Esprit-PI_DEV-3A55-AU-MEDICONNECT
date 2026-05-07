<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use App\Service\OrderAnalyticsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:update-analytics',
    description: 'Scheduler job: Update customer analytics and segments (nightly).',
)]
class SchedulerUpdateAnalyticsCommand extends Command
{
    public function __construct(
        private OrderAnalyticsService $analytics,
        private UtilisateurRepository $utilisateurs,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Customer Analytics Update (Scheduler)');

        try {
            $io->info('Starting customer analytics calculation...');

            // Get all customers
            $customers = $this->utilisateurs->findAll();

            if (empty($customers)) {
                $io->info('No customers found.');
                return Command::SUCCESS;
            }

            $io->section('📊 Processing ' . count($customers) . ' Customers');

            $progressBar = $io->createProgressBar(count($customers));
            $progressBar->start();

            $segments = [
                'VIP' => 0,           // +5 orders, avg > €500
                'HIGH_VALUE' => 0,    // 3+ orders, avg > €250
                'REGULAR' => 0,       // 2+ orders, avg > €50
                'OCCASIONAL' => 0,    // 1 order
                'INACTIVE' => 0,      // 0 orders
            ];

            $updateStats = [
                'processed' => 0,
                'updated' => 0,
                'errors' => 0,
            ];

            $analyticsData = [];

            foreach ($customers as $customer) {
                try {
                    $updateStats['processed']++;

                    // Calculate customer metrics
                    $orderCount = $this->analytics->getOrderCount($customer);
                    $totalSpent = $this->analytics->getTotalSpent($customer);
                    $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
                    $lastOrderDate = $this->analytics->getLastOrderDate($customer);
                    $preferredCategory = $this->analytics->getPreferredCategory($customer);

                    // Determine segment
                    $segment = $this->getCustomerSegment(
                        $orderCount,
                        $totalSpent,
                        $avgOrderValue,
                        $lastOrderDate
                    );

                    $segments[$segment]++;

                    $analyticsData[] = [
                        'customer_id' => $customer->getId(),
                        'email' => $customer->getEmail(),
                        'segment' => $segment,
                        'order_count' => $orderCount,
                        'total_spent' => $totalSpent,
                        'avg_value' => $avgOrderValue,
                        'last_order' => $lastOrderDate?->format('Y-m-d') ?? 'Never',
                        'preferred_category' => $preferredCategory,
                    ];

                    // Update customer segment (if entity has segment field)
                    // $customer->setSegment($segment);
                    // $this->entityManager->persist($customer);

                    $updateStats['updated']++;

                } catch (\Exception $e) {
                    $updateStats['errors']++;
                    $this->logger->error('Analytics update failed for customer', [
                        'customer_id' => $customer->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Display segment distribution
            $io->section('👥 Customer Segmentation Results');
            $io->table(
                ['Segment', 'Count', 'Percentage'],
                [
                    ['VIP', $segments['VIP'], $this->percentage($segments['VIP'], count($customers))],
                    ['HIGH_VALUE', $segments['HIGH_VALUE'], $this->percentage($segments['HIGH_VALUE'], count($customers))],
                    ['REGULAR', $segments['REGULAR'], $this->percentage($segments['REGULAR'], count($customers))],
                    ['OCCASIONAL', $segments['OCCASIONAL'], $this->percentage($segments['OCCASIONAL'], count($customers))],
                    ['INACTIVE', $segments['INACTIVE'], $this->percentage($segments['INACTIVE'], count($customers))],
                ]
            );

            // Display top customers
            $io->section('⭐ Top 10 Customers by Total Spent');
            /** @var array<int, array<string, mixed>> $analyticsData */
            usort($analyticsData, fn(array $a, array $b) => $b['total_spent'] <=> $a['total_spent']);

            $topCustomers = array_slice($analyticsData, 0, 10);
            $tableRows = array_map(fn($data) => [
                $data['customer_id'],
                $data['email'],
                $data['segment'],
                $data['order_count'] . ' orders',
                sprintf('€%.2f', $data['total_spent']),
                sprintf('€%.2f', $data['avg_value']),
                $data['last_order'],
            ], $topCustomers);

            $io->table(
                ['ID', 'Email', 'Segment', 'Orders', 'Total Spent', 'Avg Value', 'Last Order'],
                $tableRows
            );

            // Calculate key metrics
            $io->section('📈 Key Metrics');

            $totalRevenue = array_sum(array_column($analyticsData, 'total_spent'));
            $avgCustomerValue = round($totalRevenue / count($customers), 2);
            $avgOrdersPerCustomer = round(array_sum(array_column($analyticsData, 'order_count')) / count($customers), 1);

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Revenue', sprintf('€%.2f', $totalRevenue)],
                    ['Avg Customer Value', sprintf('€%.2f', $avgCustomerValue)],
                    ['Avg Orders per Customer', $avgOrdersPerCustomer],
                    ['VIP Contribution', $this->calculateVipContribution($analyticsData, 'VIP')],
                    ['Top 20% Contribution', $this->calculateTopPercentileContribution($analyticsData, 0.2)],
                ]
            );

            // Log results
            $this->logger->info('Customer analytics update completed', [
                'processed' => $updateStats['processed'],
                'updated' => $updateStats['updated'],
                'errors' => $updateStats['errors'],
                'segments' => $segments,
                'total_revenue' => $totalRevenue,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success('Analytics update completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Analytics update failed: ' . $e->getMessage());

            $this->logger->error('Customer analytics update command failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    private function getCustomerSegment(
        int $orderCount,
        float $totalSpent,
        float $avgOrderValue,
        ?\DateTimeInterface $lastOrderDate
    ): string
    {
        // Check if inactive (no orders in 6 months and orderCount = 0)
        if ($orderCount === 0) {
            return 'INACTIVE';
        }

        // Check if inactive (no orders in 6 months but has history)
        if ($lastOrderDate && $lastOrderDate->diff(new \DateTime())->days > 180) {
            return 'INACTIVE';
        }

        // VIP: 5+ orders and avg > €500
        if ($orderCount >= 5 && $avgOrderValue > 500) {
            return 'VIP';
        }

        // HIGH_VALUE: 3+ orders and avg > €250
        if ($orderCount >= 3 && $avgOrderValue > 250) {
            return 'HIGH_VALUE';
        }

        // REGULAR: 2+ orders and avg > €50
        if ($orderCount >= 2 && $avgOrderValue > 50) {
            return 'REGULAR';
        }

        // OCCASIONAL: 1 order
        if ($orderCount === 1) {
            return 'OCCASIONAL';
        }

        return 'INACTIVE';
    }

    private function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100, 1) . '%';
    }

    private function calculateVipContribution(array $analyticsData, string $segment): string
    {
        $vipRevenue = array_sum(
            array_map(fn($data) => $data['segment'] === $segment ? $data['total_spent'] : 0, $analyticsData)
        );

        $totalRevenue = array_sum(array_column($analyticsData, 'total_spent'));

        if ($totalRevenue === 0) {
            return '0%';
        }

        return sprintf('%.1f%%', ($vipRevenue / $totalRevenue) * 100);
    }

    private function calculateTopPercentileContribution(array $analyticsData, float $percentile): string
    {
        $sorted = array_sort_by_column($analyticsData, 'total_spent', SORT_DESC);
        $topCount = max(1, (int) ceil(count($analyticsData) * $percentile));
        $topCustomers = array_slice($sorted, 0, $topCount);

        $topRevenue = array_sum(array_column($topCustomers, 'total_spent'));
        $totalRevenue = array_sum(array_column($analyticsData, 'total_spent'));

        if ($totalRevenue === 0) {
            return '0%';
        }

        return sprintf('%.1f%%', ($topRevenue / $totalRevenue) * 100);
    }
}

// Helper function to sort array by column
if (!function_exists('array_sort_by_column')) {
    function array_sort_by_column(array $array, string $column, int $direction = SORT_ASC): array
    {
        usort($array, fn($a, $b) => 
            $direction === SORT_DESC 
                ? $b[$column] <=> $a[$column]
                : $a[$column] <=> $b[$column]
        );

        return $array;
    }
}
