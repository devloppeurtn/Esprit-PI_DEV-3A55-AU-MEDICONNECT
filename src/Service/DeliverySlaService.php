<?php

namespace App\Service;

use App\Entity\CommandeProduit;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliverySlaService
{
    private const CUTOFF_HOUR = 16;
    private const MAX_ETA_DAYS = 5;

    private const CARRIER_BASE_DAYS = [
        'EXPRESS' => 1,
        'STANDARD' => 2,
        'ECONOMY' => 4,
    ];

    private const TRAFFIC_EXTRA_DAYS = [
        'LOW' => 0,
        'MEDIUM' => 1,
        'HIGH' => 2,
    ];

    private const CITY_EXTRA_DAYS = [
        'tunis' => 0,
        'ariana' => 0,
        'ben arous' => 0,
        'manouba' => 0,
        'nabeul' => 1,
        'bizerte' => 1,
        'sousse' => 1,
        'sfax' => 1,
        'kairouan' => 2,
        'gabes' => 2,
        'medenine' => 2,
        'gafsa' => 2,
        'kasserine' => 2,
        'tataouine' => 3,
    ];

    public function __construct(
        private CommandeProduitRepository $commandeProduitRepository,
        private EntityManagerInterface $entityManager,
        private OsrmEtaService $osrmEtaService
    ) {
    }

    /**
     * @return array{
     *   eta_at: \DateTimeImmutable,
     *   committed_at: \DateTimeImmutable,
     *   carrier: string,
     *   traffic_level: string,
     *   cutoff_applied: bool,
     *   eta_days: int,
     *   source: string
     * }
     */
    public function estimateEta(
        string $city,
        \DateTimeImmutable $orderedAt,
        ?float $destinationLat = null,
        ?float $destinationLng = null
    ): array {
        $routeTravel = $this->osrmEtaService->estimateTravel(
            $destinationLat,
            $destinationLng,
            $orderedAt
        );

        $distanceMeters = (int) ($routeTravel['distance_meters'] ?? 0);
        $durationSeconds = (int) ($routeTravel['duration_seconds'] ?? 0);
        $etaDays = $this->computeMapOnlyEtaDays($distanceMeters, $durationSeconds);
        $carrier = $this->chooseCarrierByEtaDays($etaDays);
        $etaAt = $this->addCalendarDays($orderedAt, $etaDays)->setTime(18, 0, 0);

        return [
            'eta_at' => $etaAt,
            'committed_at' => new \DateTimeImmutable('now'),
            'carrier' => $carrier,
            'traffic_level' => 'LOW',
            'cutoff_applied' => false,
            'eta_days' => $etaDays,
            'source' => 'osrm',
        ];
    }

    public function applyDelayPenalties(?\DateTimeImmutable $now = null): int
    {
        $now = $now ?? new \DateTimeImmutable('now');
        $lateOrders = $this->commandeProduitRepository->findPastEtaWithoutPenalty($now);

        if ($lateOrders === []) {
            return 0;
        }

        $updated = 0;
        foreach ($lateOrders as $commande) {
            if (!$commande instanceof CommandeProduit || $commande->getDeliveryEtaAt() === null) {
                continue;
            }

            $points = $this->computePenaltyPoints($commande, $now);
            $commande->setDeliveryDelayPenaltyPoints(
                $commande->getDeliveryDelayPenaltyPoints() + $points
            );
            $commande->setDeliverySlaBreached(true);

            $this->entityManager->persist($commande);
            $updated++;
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        return $updated;
    }

    private function resolveTrafficLevel(\DateTimeImmutable $orderedAt): string
    {
        $hour = (int) $orderedAt->format('G');
        $dayOfWeek = (int) $orderedAt->format('N');

        if (in_array($dayOfWeek, [6, 7], true)) {
            return 'MEDIUM';
        }

        if (($hour >= 7 && $hour <= 9) || ($hour >= 16 && $hour <= 19)) {
            return 'HIGH';
        }

        if ($hour >= 10 && $hour <= 15) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function getCityExtraDays(string $city): int
    {
        $normalized = $this->normalizeCityKey($city);
        if ($normalized === '') {
            return 2;
        }

        return self::CITY_EXTRA_DAYS[$normalized] ?? 2;
    }

    private function normalizeCityKey(string $city): string
    {
        $city = mb_strtolower(trim($city));
        if ($city === '') {
            return '';
        }

        $latin = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $city);
        if (is_string($latin) && $latin !== '') {
            $city = $latin;
        }

        $city = strtolower($city);
        $city = preg_replace('/[^a-z0-9 ]+/', ' ', $city) ?? $city;

        return trim((string) preg_replace('/\s+/', ' ', $city));
    }

    private function addBusinessDays(\DateTimeImmutable $date, int $days): \DateTimeImmutable
    {
        $current = $date;
        $remaining = $days;

        while ($remaining > 0) {
            $current = $current->modify('+1 day');
            $day = (int) $current->format('N');
            if (in_array($day, [6, 7], true)) {
                continue;
            }
            $remaining--;
        }

        return $current;
    }

    private function chooseCarrierByTravelDuration(int $durationInTrafficSeconds): string
    {
        $hours = $durationInTrafficSeconds / 3600;
        if ($hours <= 2) {
            return 'EXPRESS';
        }
        if ($hours <= 6) {
            return 'STANDARD';
        }

        return 'ECONOMY';
    }

    private function chooseCarrierByCityExtraDays(int $cityExtraDays): string
    {
        if ($cityExtraDays <= 0) {
            return 'EXPRESS';
        }
        if ($cityExtraDays <= 2) {
            return 'STANDARD';
        }

        return 'ECONOMY';
    }

    private function computeRouteExtraDays(int $durationInTrafficSeconds): int
    {
        $hours = $durationInTrafficSeconds / 3600;
        if ($hours <= 6) {
            return 0;
        }

        return (int) ceil(($hours - 6) / 8);
    }

    private function computePenaltyPoints(CommandeProduit $commande, \DateTimeImmutable $now): int
    {
        $eta = $commande->getDeliveryEtaAt();
        if ($eta === null) {
            return 0;
        }

        $secondsLate = max(0, $now->getTimestamp() - $eta->getTimestamp());
        $lateDays = (int) ceil($secondsLate / 86400);

        $points = 10 + ($lateDays * 2);
        if ($commande->getDeliveryCarrier() === 'EXPRESS') {
            $points += 5;
        }

        $trafficLevel = $commande->getDeliveryTrafficLevel();
        if ($trafficLevel === 'HIGH') {
            $points += 3;
        } elseif ($trafficLevel === 'MEDIUM') {
            $points += 1;
        }

        return $points;
    }

    private function clampEtaDays(int $days): int
    {
        return max(1, min(self::MAX_ETA_DAYS, $days));
    }

    private function computeMapOnlyEtaDays(int $distanceMeters, int $durationSeconds): int
    {
        if ($distanceMeters <= 0 || $durationSeconds <= 0) {
            return 1;
        }

        $distanceDays = (int) ceil(($distanceMeters / 1000) / 220);
        $durationDays = (int) ceil(($durationSeconds / 3600) / 8);

        return $this->clampEtaDays(max(1, $distanceDays, $durationDays));
    }

    private function chooseCarrierByEtaDays(int $etaDays): string
    {
        if ($etaDays <= 1) {
            return 'EXPRESS';
        }
        if ($etaDays <= 3) {
            return 'STANDARD';
        }

        return 'ECONOMY';
    }

    private function addCalendarDays(\DateTimeImmutable $date, int $days): \DateTimeImmutable
    {
        return $date->modify(sprintf('+%d day', max(1, $days)));
    }
}
