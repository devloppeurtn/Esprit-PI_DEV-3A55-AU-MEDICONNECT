<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleMapsEtaService
{
    private const DISTANCE_MATRIX_ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    private ?string $apiKey;
    private string $originAddress;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
        $this->apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? $_SERVER['GOOGLE_MAPS_API_KEY'] ?? null;
        $this->originAddress = trim((string) ($_ENV['DELIVERY_ORIGIN_ADDRESS'] ?? $_SERVER['DELIVERY_ORIGIN_ADDRESS'] ?? 'Tunis, Tunisie'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getOriginAddress(): string
    {
        return $this->originAddress;
    }

    /**
     * @return array{
     *   distance_meters:int,
     *   duration_seconds:int,
     *   duration_in_traffic_seconds:int,
     *   traffic_level:string
     * }|null
     */
    public function estimateTravel(
        string $destinationAddress,
        \DateTimeImmutable $departureAt,
        ?float $destinationLat = null,
        ?float $destinationLng = null
    ): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $destinationAddress = trim($destinationAddress);
        $hasCoordinates = $destinationLat !== null
            && $destinationLng !== null
            && $destinationLat >= -90
            && $destinationLat <= 90
            && $destinationLng >= -180
            && $destinationLng <= 180;

        if (!$hasCoordinates && $destinationAddress === '') {
            return null;
        }

        $destination = $hasCoordinates
            ? sprintf('%.7F,%.7F', $destinationLat, $destinationLng)
            : $destinationAddress;

        try {
            $response = $this->httpClient->request('GET', self::DISTANCE_MATRIX_ENDPOINT, [
                'query' => [
                    'origins' => $this->originAddress,
                    'destinations' => $destination,
                    'mode' => 'driving',
                    'departure_time' => max(time(), $departureAt->getTimestamp()),
                    'traffic_model' => 'best_guess',
                    'key' => $this->apiKey,
                ],
                'timeout' => 6,
            ]);

            $payload = $response->toArray(false);
            if (($payload['status'] ?? null) !== 'OK') {
                return null;
            }

            $element = $payload['rows'][0]['elements'][0] ?? null;
            if (!is_array($element) || ($element['status'] ?? null) !== 'OK') {
                return null;
            }

            $distanceMeters = (int) ($element['distance']['value'] ?? 0);
            $durationSeconds = (int) ($element['duration']['value'] ?? 0);
            $durationTrafficSeconds = (int) ($element['duration_in_traffic']['value'] ?? $durationSeconds);

            if ($distanceMeters <= 0 || $durationTrafficSeconds <= 0) {
                return null;
            }

            return [
                'distance_meters' => $distanceMeters,
                'duration_seconds' => max(1, $durationSeconds),
                'duration_in_traffic_seconds' => max(1, $durationTrafficSeconds),
                'traffic_level' => $this->resolveTrafficLevel($durationSeconds, $durationTrafficSeconds),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Google Maps ETA fallback', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function resolveTrafficLevel(int $normalDuration, int $trafficDuration): string
    {
        $ratio = $trafficDuration / max(1, $normalDuration);

        if ($ratio <= 1.10) {
            return 'LOW';
        }

        if ($ratio <= 1.35) {
            return 'MEDIUM';
        }

        return 'HIGH';
    }
}
