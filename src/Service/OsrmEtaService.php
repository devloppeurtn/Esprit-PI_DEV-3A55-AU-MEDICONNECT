<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OsrmEtaService
{
    private string $originLat;
    private string $originLng;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
        $this->originLat = (string) ($_ENV['DELIVERY_ORIGIN_LAT'] ?? $_SERVER['DELIVERY_ORIGIN_LAT'] ?? '36.8065');
        $this->originLng = (string) ($_ENV['DELIVERY_ORIGIN_LNG'] ?? $_SERVER['DELIVERY_ORIGIN_LNG'] ?? '10.1815');
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
        ?float $destinationLat,
        ?float $destinationLng,
        \DateTimeImmutable $departureAt
    ): ?array {
        if (!$this->isValidCoordinates($destinationLat, $destinationLng)) {
            return null;
        }

        $originLat = (float) $this->originLat;
        $originLng = (float) $this->originLng;
        $destLat = (float) $destinationLat;
        $destLng = (float) $destinationLng;

        try {
            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%.7F,%.7F;%.7F,%.7F',
                $originLng,
                $originLat,
                $destLng,
                $destLat
            );

            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'overview' => 'false',
                    'alternatives' => 'false',
                    'steps' => 'false',
                ],
                'timeout' => 6,
            ]);

            $payload = $response->toArray(false);
            if (($payload['code'] ?? null) !== 'Ok') {
                return $this->buildFallbackTravel($originLat, $originLng, $destLat, $destLng);
            }

            $route = $payload['routes'][0] ?? null;
            if (!is_array($route)) {
                return $this->buildFallbackTravel($originLat, $originLng, $destLat, $destLng);
            }

            $durationSeconds = (int) ($route['duration'] ?? 0);
            $distanceMeters = (int) ($route['distance'] ?? 0);
            if ($durationSeconds <= 0 || $distanceMeters <= 0) {
                return $this->buildFallbackTravel($originLat, $originLng, $destLat, $destLng);
            }

            return [
                'distance_meters' => $distanceMeters,
                'duration_seconds' => $durationSeconds,
                'duration_in_traffic_seconds' => max(1, $durationSeconds),
                'traffic_level' => 'LOW',
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('OSRM ETA fallback', ['error' => $e->getMessage()]);
            return $this->buildFallbackTravel($originLat, $originLng, $destLat, $destLng);
        }
    }

    private function buildFallbackTravel(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng
    ): array {
        $distanceMeters = max(1000, (int) round($this->haversineDistanceMeters($originLat, $originLng, $destLat, $destLng)));
        $averageSpeedMetersPerSecond = 11.11; // ~40 km/h
        $durationSeconds = max(900, (int) ceil($distanceMeters / $averageSpeedMetersPerSecond));

        return [
            'distance_meters' => $distanceMeters,
            'duration_seconds' => $durationSeconds,
            'duration_in_traffic_seconds' => $durationSeconds,
            'traffic_level' => 'LOW',
        ];
    }

    private function haversineDistanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }

    private function isValidCoordinates(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }
}
