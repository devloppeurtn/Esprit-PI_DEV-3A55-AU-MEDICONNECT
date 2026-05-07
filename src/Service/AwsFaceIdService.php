<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Aws\Rekognition\RekognitionClient;
use Aws\Rekognition\Exception\RekognitionException;

class AwsFaceIdService
{
    private RekognitionClient $client;

    public function __construct(
        private readonly string $region,
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $collectionId,
        private readonly ?string $sessionToken = null,
        private readonly float $matchThreshold = 90.0,
        private readonly ?string $endpoint = null,
        private readonly ?string $caBundle = null
    ) {
        $config = [
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->accessKeyId,
                'secret' => $this->secretAccessKey,
            ],
        ];

        if ($this->sessionToken) {
            $config['credentials']['token'] = $this->sessionToken;
        }

        if ($this->endpoint) {
            $config['endpoint'] = $this->endpoint;
        }

        if ($this->caBundle) {
            $config['http'] = ['verify' => $this->caBundle];
        }

        $this->client = new RekognitionClient($config);
    }

    public function registerFace(Utilisateur $user, string $imageBase64): void
    {
        $bytes = $this->decodeBase64Image($imageBase64);
        $this->ensureCollectionExists();

        $externalId = (string) $user->getId();
        $this->deleteFacesByExternalId($externalId);

        $result = $this->client->indexFaces([
            'CollectionId' => $this->collectionId,
            'ExternalImageId' => $externalId,
            'Image' => ['Bytes' => $bytes],
            'DetectionAttributes' => [],
        ]);

        $faceRecords = $result->get('FaceRecords') ?? [];
        if ($faceRecords === []) {
            $unindexed = $result->get('UnindexedFaces') ?? [];
            $reason = $unindexed[0]['Reasons'][0] ?? 'Aucun visage detecte.';
            throw new \RuntimeException('Enregistrement Face ID echoue: ' . $reason);
        }

        $user->setBiometricEnabled(true);
    }

    public function verifyFace(Utilisateur $user, string $imageBase64): bool
    {
        if (!$user->isBiometricEnabled()) {
            return false;
        }

        $bytes = $this->decodeBase64Image($imageBase64);
        $this->ensureCollectionExists();

        $result = $this->client->searchFacesByImage([
            'CollectionId' => $this->collectionId,
            'Image' => ['Bytes' => $bytes],
            'FaceMatchThreshold' => $this->matchThreshold,
            'MaxFaces' => 5,
        ]);

        $externalId = (string) $user->getId();
        foreach ($result->get('FaceMatches') ?? [] as $match) {
            $face = $match['Face'] ?? [];
            if (($face['ExternalImageId'] ?? null) === $externalId) {
                return true;
            }
        }

        return false;
    }

    public function unregisterFace(Utilisateur $user): void
    {
        $this->ensureCollectionExists();
        $externalId = (string) $user->getId();
        $this->deleteFacesByExternalId($externalId);
        $user->setBiometricEnabled(false);
    }

    private function ensureCollectionExists(): void
    {
        try {
            $this->client->describeCollection(['CollectionId' => $this->collectionId]);
        } catch (RekognitionException $e) {
            if ($e->getAwsErrorCode() !== 'ResourceNotFoundException') {
                throw $e;
            }
            $this->client->createCollection(['CollectionId' => $this->collectionId]);
        }
    }

    private function deleteFacesByExternalId(string $externalImageId): void
    {
        $faceIds = $this->listFaceIdsByExternalId($externalImageId);
        if ($faceIds === []) {
            return;
        }

        $this->client->deleteFaces([
            'CollectionId' => $this->collectionId,
            'FaceIds' => $faceIds,
        ]);
    }

    /**
     * @return list<string>
     */
    private function listFaceIdsByExternalId(string $externalImageId): array
    {
        $faceIds = [];
        $nextToken = null;

        do {
            $params = [
                'CollectionId' => $this->collectionId,
                'MaxResults' => 100,
            ];
            if ($nextToken) {
                $params['NextToken'] = $nextToken;
            }

            $result = $this->client->listFaces($params);
            foreach ($result->get('Faces') ?? [] as $face) {
                if (($face['ExternalImageId'] ?? null) === $externalImageId && isset($face['FaceId'])) {
                    $faceIds[] = (string) $face['FaceId'];
                }
            }

            $nextToken = $result->get('NextToken') ?: null;
        } while ($nextToken);

        return $faceIds;
    }

    private function decodeBase64Image(string $imageBase64): string
    {
        $payload = $imageBase64;
        if (str_contains($payload, 'base64,')) {
            $payload = substr($payload, strpos($payload, 'base64,') + 7);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || $decoded === '') {
            throw new \InvalidArgumentException('Image base64 invalide.');
        }

        return $decoded;
    }
}
