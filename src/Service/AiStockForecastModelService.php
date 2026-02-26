<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AiStockForecastModelService
{
    private const DEFAULT_MODEL_RELATIVE_PATH = 'var/ml/stock_model.json';

    private ?array $cachedModel = null;
    private ?int $cachedMtime = null;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    ) {
    }

    public function hasModel(): bool
    {
        return is_file($this->getModelPath());
    }

    public function getModelPath(): string
    {
        $projectDir = rtrim((string) $this->parameterBag->get('kernel.project_dir'), '/\\');
        $configured = $_ENV['AI_STOCK_MODEL_PATH'] ?? $_SERVER['AI_STOCK_MODEL_PATH'] ?? null;
        $relative = trim((string) ($configured ?: self::DEFAULT_MODEL_RELATIVE_PATH));

        if (preg_match('/^[a-zA-Z]:\\\\/', $relative) === 1 || str_starts_with($relative, '/')) {
            return $relative;
        }

        return $projectDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    }

    public function predictNext7Days(array $features): ?float
    {
        $model = $this->loadModel();
        if ($model === null) {
            return null;
        }

        $featureNames = $model['feature_names'] ?? null;
        $means = $model['feature_means'] ?? null;
        $stds = $model['feature_stds'] ?? null;
        $weights = $model['weights'] ?? null;
        $bias = $model['bias'] ?? null;

        if (!is_array($featureNames) || !is_array($means) || !is_array($stds) || !is_array($weights) || !is_numeric($bias)) {
            return null;
        }

        $prediction = (float) $bias;

        foreach ($featureNames as $name) {
            $key = (string) $name;
            $raw = (float) ($features[$key] ?? 0.0);
            $mean = (float) ($means[$key] ?? 0.0);
            $std = (float) ($stds[$key] ?? 1.0);
            $weight = (float) ($weights[$key] ?? 0.0);

            if ($std <= 0.0) {
                $std = 1.0;
            }

            $normalized = ($raw - $mean) / $std;
            $prediction += ($weight * $normalized);
        }

        if (!is_finite($prediction)) {
            return null;
        }

        return max(0.0, $prediction);
    }

    private function loadModel(): ?array
    {
        $path = $this->getModelPath();
        if (!is_file($path)) {
            return null;
        }

        $mtime = filemtime($path) ?: null;
        if ($this->cachedModel !== null && $this->cachedMtime !== null && $mtime !== null && $this->cachedMtime === $mtime) {
            return $this->cachedModel;
        }

        try {
            $json = (string) file_get_contents($path);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                return null;
            }

            $this->cachedModel = $decoded;
            $this->cachedMtime = $mtime;

            return $decoded;
        } catch (\Throwable $e) {
            $this->logger->warning('Unable to load stock AI model, fallback to rules.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

