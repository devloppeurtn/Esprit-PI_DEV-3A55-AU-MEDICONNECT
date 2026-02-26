<?php

namespace App\Command;

use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:order-audit-report',
    description: 'Scheduler job: Generate order workflow state transitions audit report.',
)]
class SchedulerOrderAuditReportCommand extends Command
{
    private const DEFAULT_DAYS = 1;

    public function __construct(
        private CommandeProduitRepository $commandes,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of days back to audit',
            (string) self::DEFAULT_DAYS
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = max(1, (int) $input->getOption('days'));

        $io->title('Order Workflow Audit Report (Scheduler)');

        try {
            $from = (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'))->setTime(0, 0, 0);
            $to = new \DateTime();

            $io->info(sprintf('Auditing orders from %s to %s', 
                $from->format('Y-m-d'),
                $to->format('Y-m-d')
            ));

            // Get all orders created in period
            $allOrders = $this->commandes->findByDateRange($from, $to);

            if (empty($allOrders)) {
                $io->info('No orders found in the specified period.');
                return Command::SUCCESS;
            }

            // Analyze state distribution
            $states = [];
            $stateTransitions = [];

            foreach ($allOrders as $order) {
                $state = $order->getStatut()->value;
                $states[$state] = ($states[$state] ?? 0) + 1;

                // Track age in current state
                $orderAge = $order->getDateCommande()?->diff(new \DateTime())->days ?? 0;
                
                if (!isset($stateTransitions[$state])) {
                    $stateTransitions[$state] = [];
                }
                $stateTransitions[$state][] = $orderAge;
            }

            // Display state distribution
            $io->section('📊 Order State Distribution');
            $tableRows = [];
            foreach ($states as $state => $count) {
                $avgAge = !empty($stateTransitions[$state]) 
                    ? round(array_sum($stateTransitions[$state]) / count($stateTransitions[$state]), 1)
                    : 0;
                
                $tableRows[] = [
                    $this->getStateEmoji($state) . ' ' . $state,
                    $count,
                    $avgAge . ' days',
                ];
            }
            $io->table(['State', 'Count', 'Avg Age'], $tableRows);

            // Identify stuck orders
            $io->section('🔍 Orders Stuck in States');
            $stuckOrders = [];

            foreach ($allOrders as $order) {
                $state = $order->getStatut()->value;
                $age = $order->getDateCommande()?->diff(new \DateTime())->days ?? 0;

                // Define thresholds for "stuck"
                $threshold = match ($state) {
                    'EN_ATTENTE' => 7,    // More than 7 days without validation
                    'VALIDEE' => 5,       // More than 5 days without preparation
                    'PREPAREE' => 3,      // More than 3 days without shipment
                    'LIVREE' => 0,        // Should not be here
                    'ANNULEE' => 0,       // Expected state
                    default => 10,
                };

                if ($age > $threshold) {
                    $stuckOrders[] = [
                        'id' => $order->getId(),
                        'state' => $state,
                        'age' => $age,
                        'threshold' => $threshold,
                    ];
                }
            }

            if (!empty($stuckOrders)) {
                $tableRows = array_map(fn($item) => [
                    $item['id'],
                    $this->getStateEmoji($item['state']) . ' ' . $item['state'],
                    sprintf('%d days (⚠️ threshold: %d)', $item['age'], $item['threshold']),
                ], $stuckOrders);

                $io->table(['Order ID', 'State', 'Age'], $tableRows);
                
                $this->logger->warning('Order audit: Stuck orders detected', [
                    'stuck_count' => count($stuckOrders),
                    'orders' => array_column($stuckOrders, 'id'),
                ]);
            } else {
                $io->info('✅ No stuck orders detected.');
            }

            // Workflow completion metrics
            $io->section('📈 Workflow Metrics');
            $totalOrders = count($allOrders);
            $completed = ($states['LIVREE'] ?? 0) + ($states['ANNULEE'] ?? 0);
            $inProgress = ($states['VALIDEE'] ?? 0) + ($states['PREPAREE'] ?? 0);
            $pending = $states['EN_ATTENTE'] ?? 0;

            $completionRate = $totalOrders > 0 ? round(($completed / $totalOrders) * 100, 1) : 0;

            $io->table(
                ['Metric', 'Count', 'Percentage'],
                [
                    ['Total Orders', $totalOrders, '100%'],
                    ['Completed (LIVREE + ANNULEE)', $completed, $completionRate . '%'],
                    ['In Progress (VALIDEE + PREPAREE)', $inProgress, round(($inProgress / $totalOrders) * 100, 1) . '%'],
                    ['Pending (EN_ATTENTE)', $pending, round(($pending / $totalOrders) * 100, 1) . '%'],
                ]
            );

            // Log audit results
            $this->logger->info('Order workflow audit completed', [
                'period_days' => $days,
                'total_orders' => $totalOrders,
                'states' => $states,
                'stuck_count' => count($stuckOrders),
                'completion_rate' => $completionRate . '%',
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success('Audit report generated successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Audit failed: ' . $e->getMessage());

            $this->logger->error('Order audit report failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    private function getStateEmoji(string $state): string
    {
        return match ($state) {
            'EN_ATTENTE' => '⏳',
            'VALIDEE' => '✅',
            'PREPAREE' => '📦',
            'LIVREE' => '🚚',
            'ANNULEE' => '❌',
            default => '❓',
        };
    }
}
