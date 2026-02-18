<?php

namespace App\Command;

use App\Service\DeliverySlaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:orders:apply-sla-penalties',
    description: 'Apply internal SLA penalties to orders that missed committed delivery ETA.',
)]
class ApplyDeliverySlaPenaltiesCommand extends Command
{
    public function __construct(private DeliverySlaService $deliverySlaService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $updated = $this->deliverySlaService->applyDelayPenalties();

        if ($updated > 0) {
            $io->warning(sprintf('%d delayed order(s) penalized for SLA breach.', $updated));
        } else {
            $io->text('No delayed order pending SLA penalty.');
        }

        return Command::SUCCESS;
    }
}

