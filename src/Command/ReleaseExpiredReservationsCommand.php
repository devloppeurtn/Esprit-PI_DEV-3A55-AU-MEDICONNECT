<?php

namespace App\Command;

use App\Service\StockReservationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:orders:release-expired-reservations',
    description: 'Release reserved stock for unpaid orders older than 10 minutes.',
)]
class ReleaseExpiredReservationsCommand extends Command
{
    public function __construct(private StockReservationService $stockReservationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $released = $this->stockReservationService->releaseExpiredReservations();

        if ($released > 0) {
            $io->success(sprintf('%d expired reservation(s) released.', $released));
        } else {
            $io->text('No expired reservation to release.');
        }

        return Command::SUCCESS;
    }
}

