<?php

namespace App\Command;

use App\Service\SchedulerOrchestratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduler:run',
    description: 'Run due scheduled tasks (stock reservations, SLA penalties, stock alert scan).',
)]
class SchedulerRunCommand extends Command
{
    public function __construct(private SchedulerOrchestratorService $schedulerOrchestratorService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force-run all tasks now')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON results');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $asJson = (bool) $input->getOption('json');

        $results = $this->schedulerOrchestratorService->runDueTasks($force);

        if ($asJson) {
            $output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = [];
            $errors = 0;
            foreach ($results as $item) {
                if (($item['status'] ?? '') === 'error') {
                    $errors++;
                }

                $rows[] = [
                    $item['task'] ?? '-',
                    ($item['executed'] ?? false) ? 'yes' : 'no',
                    strtoupper((string) ($item['status'] ?? 'unknown')),
                    $item['message'] ?? '-',
                    $item['next_run_at'] ?? '-',
                ];
            }

            $io->table(['Task', 'Executed', 'Status', 'Message', 'Next run'], $rows);

            if ($errors > 0) {
                $io->warning(sprintf('Scheduler finished with %d error(s).', $errors));
                return Command::FAILURE;
            }

            $io->success('Scheduler run completed successfully.');
        }

        return Command::SUCCESS;
    }
}

