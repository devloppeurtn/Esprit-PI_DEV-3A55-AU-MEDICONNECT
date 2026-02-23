<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SchedulerOrchestratorService
{
    /**
     * @var array<string, int>
     */
    private const TASK_INTERVALS = [
        'release_expired_reservations' => 300, // every 5 min
        'apply_sla_penalties' => 900,          // every 15 min
        'stock_alert_scan' => 3600,            // every 1 hour
    ];

    public function __construct(
        private StockReservationService $stockReservationService,
        private DeliverySlaService $deliverySlaService,
        private StockDemandForecastService $stockDemandForecastService,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return list<array{
     *   task:string,
     *   status:string,
     *   executed:bool,
     *   message:string,
     *   last_run_at:?string,
     *   next_run_at:string
     * }>
     */
    public function runDueTasks(bool $force = false, ?\DateTimeImmutable $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable('now');
        $state = $this->loadState();
        $results = [];

        foreach (self::TASK_INTERVALS as $task => $intervalSeconds) {
            $lastRunAt = $this->parseTime($state['tasks'][$task]['last_run_at'] ?? null);
            $nextRunAt = $lastRunAt
                ? $lastRunAt->modify(sprintf('+%d seconds', $intervalSeconds))
                : $now;

            $isDue = $force || $lastRunAt === null || $now >= $nextRunAt;
            if (!$isDue) {
                $results[] = [
                    'task' => $task,
                    'status' => 'skipped',
                    'executed' => false,
                    'message' => 'Not due yet',
                    'last_run_at' => $lastRunAt?->format(DATE_ATOM),
                    'next_run_at' => $nextRunAt->format(DATE_ATOM),
                ];
                continue;
            }

            try {
                $message = $this->runTask($task, $now);
                $state['tasks'][$task]['last_run_at'] = $now->format(DATE_ATOM);
                $state['tasks'][$task]['last_status'] = 'ok';
                $state['tasks'][$task]['last_message'] = $message;

                $results[] = [
                    'task' => $task,
                    'status' => 'ok',
                    'executed' => true,
                    'message' => $message,
                    'last_run_at' => $now->format(DATE_ATOM),
                    'next_run_at' => $now->modify(sprintf('+%d seconds', $intervalSeconds))->format(DATE_ATOM),
                ];
            } catch (\Throwable $e) {
                $state['tasks'][$task]['last_run_at'] = $now->format(DATE_ATOM);
                $state['tasks'][$task]['last_status'] = 'error';
                $state['tasks'][$task]['last_message'] = $e->getMessage();

                $this->logger->error('Scheduler task failed', [
                    'task' => $task,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'task' => $task,
                    'status' => 'error',
                    'executed' => true,
                    'message' => $e->getMessage(),
                    'last_run_at' => $now->format(DATE_ATOM),
                    'next_run_at' => $now->modify(sprintf('+%d seconds', $intervalSeconds))->format(DATE_ATOM),
                ];
            }
        }

        $state['last_tick_at'] = $now->format(DATE_ATOM);
        $this->saveState($state);

        return $results;
    }

    private function runTask(string $task, \DateTimeImmutable $now): string
    {
        return match ($task) {
            'release_expired_reservations' => sprintf(
                '%d reservation(s) released',
                $this->stockReservationService->releaseExpiredReservations()
            ),
            'apply_sla_penalties' => sprintf(
                '%d late order(s) penalized',
                $this->deliverySlaService->applyDelayPenalties($now)
            ),
            'stock_alert_scan' => sprintf(
                '%d stock alert(s) detected',
                count($this->stockDemandForecastService->getLowStockAlerts($now))
            ),
            default => throw new \InvalidArgumentException(sprintf('Unknown scheduler task "%s".', $task)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function loadState(): array
    {
        $path = $this->getStatePath();
        if (!is_file($path)) {
            return ['tasks' => []];
        }

        try {
            $json = (string) file_get_contents($path);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : ['tasks' => []];
        } catch (\Throwable) {
            return ['tasks' => []];
        }
    }

    /**
     * @param array<string, mixed> $state
     */
    private function saveState(array $state): void
    {
        $path = $this->getStatePath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create scheduler state directory.');
        }

        $ok = file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($ok === false) {
            throw new \RuntimeException('Unable to write scheduler state file.');
        }
    }

    private function getStatePath(): string
    {
        $projectDir = rtrim((string) $this->parameterBag->get('kernel.project_dir'), '/\\');
        return $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'scheduler' . DIRECTORY_SEPARATOR . 'state.json';
    }

    private function parseTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

