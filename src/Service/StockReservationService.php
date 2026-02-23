<?php

namespace App\Service;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockReservationService
{
    private const RESERVATION_MINUTES = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommandeProduitRepository $commandeRepository,
        private OrderWorkflowService $orderWorkflowService
    ) {
    }

    public function getReservationDurationMinutes(): int
    {
        return self::RESERVATION_MINUTES;
    }

    public function getReservationExpiresAt(CommandeProduit $commande): ?\DateTimeImmutable
    {
        if ($commande->getStatut() !== StatutCommande::EN_ATTENTE || !$commande->getDateCommande()) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($commande->getDateCommande())
            ->modify('+' . self::RESERVATION_MINUTES . ' minutes');
    }

    public function isReservationExpired(CommandeProduit $commande, ?\DateTimeImmutable $now = null): bool
    {
        $expiresAt = $this->getReservationExpiresAt($commande);
        if ($expiresAt === null) {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable();
        return $expiresAt <= $now;
    }

    public function releaseIfExpired(CommandeProduit $commande): bool
    {
        if (!$this->isReservationExpired($commande)) {
            return false;
        }

        $this->releaseReservation($commande);
        $this->entityManager->flush();

        return true;
    }

    public function releaseExpiredReservations(): int
    {
        $cutoff = (new \DateTimeImmutable())
            ->modify('-' . self::RESERVATION_MINUTES . ' minutes');

        $expiredReservations = $this->commandeRepository->findExpiredPendingReservations($cutoff);
        if ($expiredReservations === []) {
            return 0;
        }

        $released = 0;
        foreach ($expiredReservations as $commande) {
            if (!$commande instanceof CommandeProduit || $commande->getStatut() !== StatutCommande::EN_ATTENTE) {
                continue;
            }

            $this->releaseReservation($commande);
            $released++;
        }

        if ($released > 0) {
            $this->entityManager->flush();
        }

        return $released;
    }

    private function releaseReservation(CommandeProduit $commande): void
    {
        foreach ($commande->getLignesCommande() as $ligne) {
            $produit = $ligne->getProduit();
            if ($produit === null) {
                continue;
            }

            $produit->setStock(($produit->getStock() ?? 0) + $ligne->getQuantite());
            $this->entityManager->persist($produit);
        }

        $this->orderWorkflowService->apply($commande, 'cancel');
        $this->entityManager->persist($commande);
    }
}
