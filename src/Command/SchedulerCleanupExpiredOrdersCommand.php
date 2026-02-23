<?php

namespace App\Command;

use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:cleanup-expired-orders',
    description: 'Scheduler job: Clean up expired order reservations (>24h old, EN_ATTENTE status).',
)]
class SchedulerCleanupExpiredOrdersCommand extends Command
{
    private const DEFAULT_HOURS = 24;

    public function __construct(
        private CommandeProduitRepository $commandeRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'hours',
            null,
            InputOption::VALUE_REQUIRED,
            'Hours old to consider expired',
            (string) self::DEFAULT_HOURS
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = max(1, (int) $input->getOption('hours'));

        $io->title('Expired Order Cleanup (Scheduler)');
        $io->info(sprintf('Cleaning up orders pending for > %d hours...', $hours));

        try {
            $cutoffDate = (new \DateTime())->sub(new \DateInterval('PT' . $hours . 'H'));

            // Find expired pending orders
            $expiredOrders = $this->commandeRepository->createQueryBuilder('c')
                ->where('c.statut = :status')
                ->andWhere('c.dateCommande < :cutoff')
                ->setParameter('status', StatutCommande::EN_ATTENTE)
                ->setParameter('cutoff', $cutoffDate)
                ->getQuery()
                ->getResult();

            if (empty($expiredOrders)) {
                $io->info('No expired orders found.');
                $this->logger->info('Expired order cleanup: No orders found', [
                    'hours' => $hours,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                ]);
                return Command::SUCCESS;
            }

            $io->section(sprintf('Found %d expired orders', count($expiredOrders)));

            // Display them
            $tableRows = [];
            foreach ($expiredOrders as $order) {
                $age = $order->getDateCommande()?->diff(new \DateTime())->h ?? -1;
                $tableRows[] = [
                    $order->getId(),
                    $order->getUtilisateur()?->getEmail() ?? 'Unknown',
                    $order->getDateCommande()?->format('Y-m-d H:i') ?? 'N/A',
                    $age . ' hours ago',
                ];
            }

            $io->table(
                ['Order ID', 'Customer', 'Created', 'Age'],
                $tableRows
            );

            // Option to delete
            if (!$io->confirm('Delete these expired orders and release stock?', false)) {
                $io->info('Cancelled. No orders deleted.');
                return Command::SUCCESS;
            }

            // Perform cleanup
            $deletedCount = 0;
            foreach ($expiredOrders as $order) {
                try {
                    // Release stock for each line item
                    foreach ($order->getLignesCommande() as $ligne) {
                        $produit = $ligne->getProduit();
                        if ($produit) {
                            $newStock = (int)$produit->getStock() + (int)$ligne->getQuantite();
                            $produit->setStock($newStock);
                            $io->writeln(sprintf(
                                '  ↩️ Released %d units of %s',
                                $ligne->getQuantite(),
                                $produit->getNom()
                            ));
                        }
                    }

                    // Delete the order
                    $this->entityManager->remove($order);
                    $deletedCount++;

                } catch (\Exception $e) {
                    $this->logger->error('Failed to delete order', [
                        'order_id' => $order->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Persist changes
            $this->entityManager->flush();

            // Log results
            $this->logger->info('Expired orders cleanup completed', [
                'deleted_count' => $deletedCount,
                'hours_threshold' => $hours,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $io->success(sprintf(
                'Cleanup complete: %d orders deleted, stock released',
                $deletedCount
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Cleanup failed: ' . $e->getMessage());

            $this->logger->error('Expired orders cleanup failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Command::FAILURE;
        }
    }
}
