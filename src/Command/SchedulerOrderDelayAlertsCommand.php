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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:scheduler:order-delay-alerts',
    description: 'Scheduler job: Monitor and alert on slow-moving (delayed) orders.',
)]
class SchedulerOrderDelayAlertsCommand extends Command
{
    public function __construct(
        private CommandeProduitRepository $commandes,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $notificationEmail = 'orders-support@mediconnect.local',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'email',
            null,
            InputOption::VALUE_REQUIRED,
            'Email address for delay alerts',
            $this->notificationEmail
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Order Delay Alerts (Scheduler)');

        try {
            $email = $input->getOption('email') ?: $this->notificationEmail;

            // Define delay thresholds (in hours)
            $delayThresholds = [
                'EN_ATTENTE' => 72,      // 3 days to validate
                'VALIDEE' => 48,         // 2 days to prepare
                'PREPAREE' => 24,        // 1 day to ship
            ];

            $delayedOrders = [];
            $now = new \DateTime();

            foreach ($delayThresholds as $state => $thresholdHours) {
                $orders = $this->commandes->findOrdersByStatus(StatutCommande::from($state));

                foreach ($orders as $order) {
                    $age = $order->getDateCommande()->diff($now);
                    $ageHours = ($age->days * 24) + $age->h;

                    if ($ageHours > $thresholdHours) {
                        $delayedOrders[] = [
                            'order' => $order,
                            'state' => $state,
                            'ageHours' => $ageHours,
                            'threshold' => $thresholdHours,
                            'overdue' => $ageHours - $thresholdHours,
                            'severity' => $this->calculateSeverity($ageHours, $thresholdHours),
                        ];
                    }
                }
            }

            if (empty($delayedOrders)) {
                $io->success('✅ No delayed orders detected!');
                
                $this->logger->info('Order delay check: All orders on schedule', [
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);

                return Command::SUCCESS;
            }

            // Sort by severity (HIGH first)
            usort($delayedOrders, fn($a, $b) => 
                ['HIGH' => 0, 'MEDIUM' => 1, 'LOW' => 2][$a['severity']] 
                <=> 
                ['HIGH' => 0, 'MEDIUM' => 1, 'LOW' => 2][$b['severity']]
            );

            // Display delayed orders
            $io->section('⚠️ Delayed Orders Detected');
            $tableRows = [];
            $severityCounts = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];

            foreach ($delayedOrders as $item) {
                $severityCounts[$item['severity']]++;
                $emoji = match ($item['severity']) {
                    'HIGH' => 'HIGH',
                    'MEDIUM' => 'MEDIUM',
                    'LOW' => 'LOW',
                    default => 'LOW',
                };

                $tableRows[] = [
                    $item['order']->getId(),
                    $emoji . ' ' . $item['state'],
                    $item['ageHours'] . ' h (threshold: ' . $item['threshold'] . ' h)',
                    '+' . $item['overdue'] . ' h overdue',
                ];
            }

            $io->table(['Order ID', 'State', 'Age', 'Overdue'], $tableRows);

            // Summary
            $io->section('📊 Delay Summary');
            $io->table(
                ['Severity', 'Count'],
                [
                    ['🔴 HIGH', $severityCounts['HIGH']],
                    ['🟠 MEDIUM', $severityCounts['MEDIUM']],
                    ['🟡 LOW', $severityCounts['LOW']],
                ]
            );

            // Send alert email
            $this->sendAlertEmail($email, $delayedOrders, $severityCounts);

            $this->logger->warning('Order delay alert sent', [
                'delayed_count' => count($delayedOrders),
                'high' => $severityCounts['HIGH'],
                'medium' => $severityCounts['MEDIUM'],
                'low' => $severityCounts['LOW'],
                'email' => $email,
            ]);

            $io->success('Delay alert email sent to: ' . $email);
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Delay alert failed: ' . $e->getMessage());

            $this->logger->error('Order delay alerts command failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    private function calculateSeverity(int $ageHours, int $thresholdHours): string
    {
        $overdue = $ageHours - $thresholdHours;
        $overduePercent = ($overdue / $thresholdHours) * 100;

        if ($overduePercent >= 100) {
            return 'HIGH';
        } elseif ($overduePercent >= 50) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function sendAlertEmail(string $recipient, array $delayedOrders, array $severityCounts): void
    {
        $emailBody = $this->buildEmailContent($delayedOrders, $severityCounts);

        $email = (new Email())
            ->from('noreply@mediconnect.local')
            ->to($recipient)
            ->subject('⚠️ MediConnect: ' . count($delayedOrders) . ' Delayed Orders Detected')
            ->html($emailBody);

        $this->mailer->send($email);
    }

    private function buildEmailContent(array $delayedOrders, array $severityCounts): string
    {
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 800px; margin: 0 auto; }
                .header { background-color: #f44336; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .summary { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #f44336; margin: 15px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #4CAF50; color: white; }
                tr:hover { background-color: #f5f5f5; }
                .high { color: #d32f2f; font-weight: bold; }
                .medium { color: #f57c00; font-weight: bold; }
                .low { color: #fbc02d; font-weight: bold; }
                .footer { color: #999; font-size: 0.9em; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚠️ Order Delay Alert</h1>
                    <p>MediConnect Scheduler Report - $timestamp</p>
                </div>

                <h2>Delayed Orders Summary</h2>
                <div class="summary">
                    <p><strong>Total Delayed Orders:</strong> {$this->countOrders($delayedOrders)}</p>
                    <p><span class="high">🔴 HIGH Priority:</span> {$severityCounts['HIGH']}</p>
                    <p><span class="medium">🟠 MEDIUM Priority:</span> {$severityCounts['MEDIUM']}</p>
                    <p><span class="low">🟡 LOW Priority:</span> {$severityCounts['LOW']}</p>
                </div>

                <h2>Detailed Report</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Current State</th>
                            <th>Age</th>
                            <th>Threshold</th>
                            <th>Overdue</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
        HTML;

        foreach ($delayedOrders as $item) {
            $severityClass = match ($item['severity']) {
                'HIGH' => 'high',
                'MEDIUM' => 'medium',
                'LOW' => 'low',
                default => 'low',
            };

            $html .= <<<HTML
                        <tr>
                            <td>{$item['order']->getId()}</td>
                            <td>{$item['state']}</td>
                            <td>{$item['ageHours']} hours</td>
                            <td>{$item['threshold']} hours</td>
                            <td>+{$item['overdue']} hours</td>
                            <td><span class="$severityClass">{$item['severity']}</span></td>
                        </tr>
            HTML;
        }

        $html .= <<<HTML
                    </tbody>
                </table>

                <h2>Recommended Actions</h2>
                <ul>
                    <li><strong>HIGH Priority:</strong> Immediate review and escalation required</li>
                    <li><strong>MEDIUM Priority:</strong> Follow up with team and check for blockers</li>
                    <li><strong>LOW Priority:</strong> Monitor closely, may resolve soon</li>
                </ul>

                <div class="footer">
                    <p>This is an automated alert from MediConnect Scheduler.</p>
                    <p>Report generated: $timestamp</p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        return $html;
    }

    private function countOrders(array $delayedOrders): int
    {
        return count(array_unique(array_map(fn($item) => $item['order']->getId(), $delayedOrders)));
    }
}





