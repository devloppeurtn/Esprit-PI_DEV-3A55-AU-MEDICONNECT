<?php

namespace App\Service;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;

class OrderWorkflowService
{
    /**
     * @var array<string, array<string, string>>
     */
    private const TRANSITIONS = [
        'pay_success' => [
            'EN_ATTENTE' => 'VALIDEE',
        ],
        'prepare' => [
            'EN_ATTENTE' => 'PREPAREE',
            'VALIDEE' => 'PREPAREE',
        ],
        'deliver' => [
            'VALIDEE' => 'LIVREE',
            'PREPAREE' => 'LIVREE',
        ],
        'cancel' => [
            'EN_ATTENTE' => 'ANNULEE',
            'VALIDEE' => 'ANNULEE',
            'PREPAREE' => 'ANNULEE',
        ],
    ];

    /**
     * @return list<string>
     */
    public function getAvailableTransitions(CommandeProduit $commande): array
    {
        $from = $commande->getStatut()->value;
        $available = [];

        foreach (self::TRANSITIONS as $transition => $map) {
            if (isset($map[$from])) {
                $available[] = $transition;
            }
        }

        return $available;
    }

    public function canApply(CommandeProduit $commande, string $transition): bool
    {
        $from = $commande->getStatut()->value;
        return isset(self::TRANSITIONS[$transition][$from]);
    }

    public function apply(
        CommandeProduit $commande,
        string $transition,
        ?\DateTimeImmutable $at = null
    ): void {
        $from = $commande->getStatut()->value;

        if (!isset(self::TRANSITIONS[$transition])) {
            throw new \InvalidArgumentException(sprintf('Unknown transition "%s".', $transition));
        }

        $targetValue = self::TRANSITIONS[$transition][$from] ?? null;
        if ($targetValue === null) {
            throw new \DomainException(sprintf(
                'Transition "%s" not allowed from "%s".',
                $transition,
                $from
            ));
        }

        $target = StatutCommande::from($targetValue);
        $commande->setStatut($target);

        if ($target === StatutCommande::LIVREE) {
            $commande->setDeliveredAt($at ?? new \DateTimeImmutable('now'));
        }
    }
}

