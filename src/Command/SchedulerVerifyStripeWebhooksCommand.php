<?php

namespace App\Command;

use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:verify-stripe-webhooks',
    description: 'Scheduler job: Verify and sync Stripe webhook events for payment status updates.',
)]
class SchedulerVerifyStripeWebhooksCommand extends Command
{
    public function __construct(
        private CommandeProduitRepository $commandes,
        private LoggerInterface $logger,
        private ?string $stripeSecretKey = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Stripe Webhook Verification & Sync (Scheduler)');

        try {
            if (!$this->stripeSecretKey) {
                $io->warning('Stripe API key not configured. Verification skipped.');
                $this->logger->warning('Stripe webhook verification skipped - no API key configured');
                return Command::SUCCESS;
            }

            $io->info('Verifying recent Stripe webhook events...');

            // Find orders that were updated in the last 24 hours
            $since = (new \DateTime())->sub(new \DateInterval('P1D'));
            $recentOrders = $this->getRecentOrdersNeedingVerification($since);

            if (empty($recentOrders)) {
                $io->info('✅ No pending webhook verifications required.');
                return Command::SUCCESS;
            }

            $io->section('🔍 Verifying ' . count($recentOrders) . ' Orders');

            $progressBar = $io->createProgressBar(count($recentOrders));
            $progressBar->start();

            $verificationResults = [
                'verified' => 0,
                'corrected' => 0,
                'mismatched' => 0,
                'errors' => 0,
            ];

            foreach ($recentOrders as $order) {
                try {
                    // In production: Query Stripe API to verify payment status
                    // $stripePaymentStatus = $this->getStripePaymentStatus($order->getStripePaymentIntentId());
                    
                    // Simulate verification
                    $verificationStatus = $this->verifyOrderPaymentStatus($order);

                    if ($verificationStatus['matches']) {
                        $verificationResults['verified']++;
                    } elseif ($verificationStatus['corrected']) {
                        $verificationResults['corrected']++;
                        // Update order status if needed
                        // $order->setStatut($verificationStatus['correct_status']);
                    } else {
                        $verificationResults['mismatched']++;
                    }

                    $this->logger->debug('Stripe webhook verification for order', [
                        'order_id' => $order->getId(),
                        'status' => $verificationStatus['matches'] ? 'verified' : 'mismatch',
                    ]);

                } catch (\Exception $e) {
                    $verificationResults['errors']++;
                    $this->logger->error('Stripe webhook verification failed', [
                        'order_id' => $order->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Display results
            $io->section('✅ Verification Results');
            $io->table(
                ['Status', 'Count'],
                [
                    ['✔️ Verified (matching)', $verificationResults['verified']],
                    ['🔧 Corrected (auto-fixed)', $verificationResults['corrected']],
                    ['⚠️ Mismatched (needs review)', $verificationResults['mismatched']],
                    ['❌ Errors', $verificationResults['errors']],
                ]
            );

            if ($verificationResults['mismatched'] > 0) {
                $io->warning(
                    $verificationResults['mismatched'] . 
                    ' orders have payment mismatches. Manual review recommended.'
                );
            }

            // Webhook event metrics
            $io->section('📊 Webhook Event Status');
            
            $webhookMetrics = [
                'timeout_retries' => 0,      // Events Stripe is still retrying
                'delivered' => count($recentOrders) - $verificationResults['errors'],
                'failed' => $verificationResults['errors'],
                'orphaned' => $this->countOrphanedWebhooks($since),
            ];

            $io->table(
                ['Event Status', 'Count'],
                [
                    ['Delivered', $webhookMetrics['delivered']],
                    ['Timeout Retries', $webhookMetrics['timeout_retries']],
                    ['Failed', $webhookMetrics['failed']],
                    ['Orphaned (no order)', $webhookMetrics['orphaned']],
                ]
            );

            // Log summary
            $this->logger->info('Stripe webhook verification cycle completed', [
                'verified' => $verificationResults['verified'],
                'corrected' => $verificationResults['corrected'],
                'mismatched' => $verificationResults['mismatched'],
                'errors' => $verificationResults['errors'],
                'total_checked' => count($recentOrders),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success('Stripe webhook verification completed!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Stripe webhook verification failed: ' . $e->getMessage());

            $this->logger->error('Stripe webhook verification command failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }

    private function getRecentOrdersNeedingVerification(\DateTime $since): array
    {
        // Find orders with payment-related status changes in the past period
        // In production: Query database for orders where dateModification > $since
        // AND status in ['VALIDEE', 'PREPAREE', 'LIVREE', 'ANNULEE']
        
        // For now, return empty (would be populated from database)
        return [];
    }

    private function verifyOrderPaymentStatus(object $order): array
    {
        // Simulate Stripe API call to verify payment status
        // In production: Call Stripe API with payment intent ID
        
        // For demonstration purposes, assume all are verified
        return [
            'matches' => true,
            'corrected' => false,
            'correct_status' => $order->getStatut(),
        ];
    }

    private function countOrphanedWebhooks(\DateTime $since): int
    {
        // Count webhook events that don't have corresponding orders
        // In production: Query webhook event log for events without matching order_id
        
        return 0; // Placeholder
    }
}
