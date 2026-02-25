<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleVisionOcrService
{
    private ?string $cachedAccessToken = null;
    private int $cachedAccessTokenExpiresAt = 0;
    /** @var array<string,string>|null */
    private ?array $dotenvFallback = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function isConfigured(): bool
    {
        if ($this->getApiKey() !== null) {
            return true;
        }

        try {
            return $this->getServiceAccountData() !== null;
        } catch (\Throwable $e) {
            $this->logger?->warning('Google Vision OCR config parsing failed', ['error' => $e->getMessage()]);
            return false;
        }
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

        $base64Content = base64_encode($binary);

        $url = 'https://vision.googleapis.com/v1/images:annotate';
        $headers = ['Content-Type' => 'application/json'];

        $apiKey = $this->getApiKey();
        if ($apiKey !== null) {
            $url .= '?key=' . rawurlencode($apiKey);
        } else {
            $accessToken = $this->getAccessTokenFromServiceAccount();
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        try {
            $data = $this->callVisionApi($url, $headers, $base64Content, 'DOCUMENT_TEXT_DETECTION');
        } catch (\Throwable $e) {
            $this->logger?->error('Google Vision OCR request failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur de connexion au service OCR.');
        }

        $errorMessage = $data['error']['message'] ?? null;
        if (is_string($errorMessage) && trim($errorMessage) !== '') {
            $this->logger?->warning('Google Vision OCR returned error payload', ['error' => $errorMessage]);
            throw new \RuntimeException('OCR indisponible: ' . $errorMessage);
        }

        $text = $data['responses'][0]['fullTextAnnotation']['text']
            ?? $data['responses'][0]['textAnnotations'][0]['description']
            ?? null;

        if (!is_string($text)) {
            try {
                // Fallback utile pour certaines images manuscrites courtes.
                $fallbackData = $this->callVisionApi($url, $headers, $base64Content, 'TEXT_DETECTION');
                $text = $fallbackData['responses'][0]['fullTextAnnotation']['text']
                    ?? $fallbackData['responses'][0]['textAnnotations'][0]['description']
                    ?? null;
            } catch (\Throwable $e) {
                $this->logger?->warning('Google Vision OCR fallback TEXT_DETECTION failed', ['error' => $e->getMessage()]);
                return '';
            }

            if (!is_string($text)) {
                return '';
            }
        }

        return trim($text);
    }

    private function getAccessTokenFromServiceAccount(): string
    {
        $now = time();
        if (
            is_string($this->cachedAccessToken)
            && $this->cachedAccessToken !== ''
            && $now < ($this->cachedAccessTokenExpiresAt - 60)
        ) {
            return $this->cachedAccessToken;
        }

        $serviceAccount = $this->getServiceAccountData();
        if ($serviceAccount === null) {
            throw new \RuntimeException(
                'OCR non configure: utilisez GOOGLE_CLOUD_VISION_API_KEY (ou GOOGLE_CLOUD_VISION_API) ou GOOGLE_CLOUD_VISION_SERVICE_ACCOUNT_JSON.'
            );
        }

        $tokenUri = (string) ($serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token');
        $clientEmail = (string) ($serviceAccount['client_email'] ?? '');
        $privateKey = (string) ($serviceAccount['private_key'] ?? '');

        if ($clientEmail === '' || $privateKey === '') {
            throw new \RuntimeException('Fichier de credentials Google invalide (client_email/private_key manquants).');
        }

        $privateKey = str_replace('\n', "\n", $privateKey);
        $assertion = $this->buildJwtAssertion($clientEmail, $privateKey, $tokenUri);

        try {
            $response = $this->httpClient->request('POST', $tokenUri, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ],
                'timeout' => 20,
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger?->error('Google OAuth token request failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Impossible d\'obtenir le token Google OAuth.');
        }

        $tokenError = $data['error_description'] ?? $data['error'] ?? null;
        if (is_string($tokenError) && trim($tokenError) !== '') {
            throw new \RuntimeException('Token Google OAuth refuse: ' . $tokenError);
        }

        $accessToken = $data['access_token'] ?? null;
        if (!is_string($accessToken) || trim($accessToken) === '') {
            throw new \RuntimeException('Token Google OAuth introuvable dans la reponse.');
        }

        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        if ($expiresIn <= 0) {
            $expiresIn = 3600;
        }

        $this->cachedAccessToken = trim($accessToken);
        $this->cachedAccessTokenExpiresAt = $now + $expiresIn;

        return $this->cachedAccessToken;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getServiceAccountData(): ?array
    {
        $raw = $this->getEnvValue('GOOGLE_CLOUD_VISION_SERVICE_ACCOUNT_JSON');

        if (!is_string($raw) || trim($raw) === '') {
            $raw = $this->getEnvValue('GOOGLE_APPLICATION_CREDENTIALS');
        }

        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);
        $json = $raw;

        if (is_file($raw)) {
            $fileJson = @file_get_contents($raw);
            if (!is_string($fileJson) || trim($fileJson) === '') {
                throw new \RuntimeException('Impossible de lire le fichier credentials Google.');
            }
            $json = $fileJson;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Credentials Google invalides: JSON non lisible.');
        }

        return $data;
    }

    private function buildJwtAssertion(string $clientEmail, string $privateKey, string $tokenUri): string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        if (!is_string($segments[0]) || !is_string($segments[1])) {
            throw new \RuntimeException('Impossible de construire le JWT Google.');
        }

        $signingInput = $segments[0] . '.' . $segments[1];
        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $privateKey, 'sha256WithRSAEncryption');
        if ($ok !== true) {
            throw new \RuntimeException('Signature JWT Google impossible. Verifiez private_key.');
        }

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function getApiKey(): ?string
    {
        $apiKey = $this->getEnvValue('GOOGLE_CLOUD_VISION_API_KEY', 'GOOGLE_CLOUD_VISION_API');

        if (!is_string($apiKey)) {
            return null;
        }

        $apiKey = trim($apiKey);

        return $apiKey !== '' ? $apiKey : null;
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
     * Fallback when env vars are not exported by PHP runtime.
     *
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

    /**
     * @param array<string,string> $headers
     *
     * @return array<string,mixed>
     */
    private function callVisionApi(string $url, array $headers, string $base64Content, string $featureType): array
    {
        $payload = [
            'requests' => [[
                'image' => [
                    'content' => $base64Content,
                ],
                'features' => [[
                    'type' => $featureType,
                ]],
                'imageContext' => [
                    'languageHints' => ['fr', 'en'],
                ],
            ]],
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => $headers,
            'json' => $payload,
            'timeout' => 25,
        ]);

        return $response->toArray(false);
    }
}
