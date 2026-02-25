<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OcrSpaceService
{
    /** @var array<string,string>|null */
    private ?array $dotenvFallback = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->getEndpoint() !== null && $this->getApiKey() !== null;
    }

    public function extractTextFromImage(string $filePath): string
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException('Image introuvable.');
        }

        $binary = @file_get_contents($filePath);
        if (!is_string($binary) || $binary === '') {
            throw new \RuntimeException('Impossible de lire l\'image.');
        }

        $endpoint = $this->getEndpoint();
        $apiKey = $this->getApiKey();
        if (!is_string($endpoint) || trim($endpoint) === '' || !is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('OCR.Space non configure. Ajoutez OCR_SPACE_API_KEY dans .env.local.');
        }

        $mimeType = @mime_content_type($filePath);
        if (!is_string($mimeType) || trim($mimeType) === '') {
            $mimeType = 'image/jpeg';
        }

        $base64Image = sprintf('data:%s;base64,%s', $mimeType, base64_encode($binary));

        $language = $this->getEnvValue('OCR_SPACE_LANGUAGE');
        if (!is_string($language) || trim($language) === '') {
            $language = 'fre';
        }

        $ocrEngine = $this->getEnvValue('OCR_SPACE_ENGINE');
        if (!is_string($ocrEngine) || trim($ocrEngine) === '') {
            $ocrEngine = '2';
        }

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'apikey' => $apiKey,
                ],
                'body' => [
                    'base64Image' => $base64Image,
                    'language' => $language,
                    'isOverlayRequired' => 'false',
                    'OCREngine' => $ocrEngine,
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                ],
                'timeout' => 40,
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger?->error('OCR.Space request failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur de connexion au service OCR.Space.');
        }

        $isErrored = $data['IsErroredOnProcessing'] ?? false;
        if ($this->toBool($isErrored)) {
            $error = $this->normalizeError(
                $data['ErrorMessage'] ?? null,
                isset($data['ErrorDetails']) ? (string) $data['ErrorDetails'] : null
            );
            throw new \RuntimeException('OCR.Space indisponible: ' . $error);
        }

        $parsedResults = $data['ParsedResults'] ?? null;
        if (!is_array($parsedResults)) {
            return '';
        }

        $chunks = [];
        foreach ($parsedResults as $item) {
            if (!is_array($item)) {
                continue;
            }
            $text = $item['ParsedText'] ?? null;
            if (is_string($text) && trim($text) !== '') {
                $chunks[] = trim($text);
            }
        }

        return trim(implode("\n\n", $chunks));
    }

    private function normalizeError(mixed $errorMessage, ?string $errorDetails): string
    {
        $parts = [];

        if (is_string($errorMessage) && trim($errorMessage) !== '') {
            $parts[] = trim($errorMessage);
        } elseif (is_array($errorMessage)) {
            foreach ($errorMessage as $msg) {
                if (is_string($msg) && trim($msg) !== '') {
                    $parts[] = trim($msg);
                }
            }
        }

        if (is_string($errorDetails) && trim($errorDetails) !== '') {
            $parts[] = trim($errorDetails);
        }

        if (count($parts) === 0) {
            return 'Erreur inconnue.';
        }

        return implode(' | ', $parts);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) !== 0;
        }
        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function getApiKey(): ?string
    {
        $apiKey = $this->getEnvValue('OCR_SPACE_API_KEY');
        if (is_string($apiKey) && trim($apiKey) !== '') {
            return trim($apiKey);
        }

        $useDemo = $this->getEnvValue('OCR_SPACE_USE_DEMO_KEY');
        if (is_string($useDemo) && in_array(strtolower(trim($useDemo)), ['0', 'false', 'no', 'off'], true)) {
            return null;
        }

        return 'helloworld';
    }

    private function getEndpoint(): ?string
    {
        $endpoint = $this->getEnvValue('OCR_SPACE_ENDPOINT');
        if (!is_string($endpoint) || trim($endpoint) === '') {
            $endpoint = 'https://api.ocr.space/parse/image';
        }

        $endpoint = trim($endpoint);

        return $endpoint !== '' ? $endpoint : null;
    }

    private function getEnvValue(string ...$names): ?string
    {
        foreach ($names as $name) {
            $value = $_ENV[$name]
                ?? $_SERVER[$name]
                ?? getenv($name)
                ?? null;

            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $fallback = $this->getDotenvFallback();
        foreach ($names as $name) {
            if (!isset($fallback[$name])) {
                continue;
            }
            $value = trim((string) $fallback[$name]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string,string>
     */
    private function getDotenvFallback(): array
    {
        if (is_array($this->dotenvFallback)) {
            return $this->dotenvFallback;
        }

        $this->dotenvFallback = [];
        $projectDir = dirname(__DIR__, 2);
        $files = [$projectDir . DIRECTORY_SEPARATOR . '.env', $projectDir . DIRECTORY_SEPARATOR . '.env.local'];

        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $lines = @file($file, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                if (!is_string($line)) {
                    continue;
                }

                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                if (str_starts_with($trimmed, 'export ')) {
                    $trimmed = trim(substr($trimmed, 7));
                }

                $eqPos = strpos($trimmed, '=');
                if ($eqPos === false || $eqPos === 0) {
                    continue;
                }

                $key = trim(substr($trimmed, 0, $eqPos));
                if ($key === '' || !preg_match('/^[A-Z0-9_]+$/', $key)) {
                    continue;
                }

                $rawValue = trim(substr($trimmed, $eqPos + 1));
                if ($rawValue === '') {
                    $this->dotenvFallback[$key] = '';
                    continue;
                }

                if (
                    (str_starts_with($rawValue, '"') && str_ends_with($rawValue, '"'))
                    || (str_starts_with($rawValue, '\'') && str_ends_with($rawValue, '\''))
                ) {
                    $rawValue = substr($rawValue, 1, -1);
                } else {
                    $hashPos = strpos($rawValue, ' #');
                    if ($hashPos !== false) {
                        $rawValue = rtrim(substr($rawValue, 0, $hashPos));
                    }
                }

                $rawValue = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $rawValue);
                $this->dotenvFallback[$key] = $rawValue;
            }
        }

        return $this->dotenvFallback;
    }
}

