<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client pour le service Python Face ID (face_recognition).
 * Appelle l'API FastAPI pour enregistrement et vérification du visage.
 */
class FaceIdClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $faceIdServiceUrl
    ) {
    }

    public function register(int $userId, string $imageBase64): array
    {
        $response = $this->httpClient->request('POST', $this->faceIdServiceUrl . '/register', [
            'json' => [
                'user_id' => (string) $userId,
                'image_base64' => $imageBase64,
            ],
            'timeout' => 30,
        ]);
        return $response->toArray();
    }

    public function verify(int $userId, string $imageBase64): array
    {
        $response = $this->httpClient->request('POST', $this->faceIdServiceUrl . '/verify', [
            'json' => [
                'user_id' => (string) $userId,
                'image_base64' => $imageBase64,
            ],
            'timeout' => 30,
        ]);
        return $response->toArray();
    }

    public function unregister(int $userId): void
    {
        $this->httpClient->request('DELETE', $this->faceIdServiceUrl . '/register/' . $userId, [
            'timeout' => 5,
        ]);
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->faceIdServiceUrl . '/health', ['timeout' => 2]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }
}
