<?php

namespace App\Tests\Service;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;
use App\Service\OrderWorkflowService;
use PHPUnit\Framework\TestCase;

class OrderWorkflowServiceTest extends TestCase
{
    public function testApplyPaySuccessTransition(): void
    {
        $service = new OrderWorkflowService();
        $commande = new CommandeProduit();
        $commande->setStatut(StatutCommande::EN_ATTENTE);

        $service->apply($commande, 'pay_success', new \DateTimeImmutable('2026-03-04 10:00'));

        $this->assertSame(StatutCommande::VALIDEE, $commande->getStatut());
    }

    public function testApplyDeliverSetsDeliveredAt(): void
    {
        $service = new OrderWorkflowService();
        $commande = new CommandeProduit();
        $commande->setStatut(StatutCommande::PREPAREE);
        $at = new \DateTimeImmutable('2026-03-04 11:00');

        $service->apply($commande, 'deliver', $at);

        $this->assertSame(StatutCommande::LIVREE, $commande->getStatut());
        $this->assertSame($at, $commande->getDeliveredAt());
    }

    public function testApplyUnknownTransitionThrows(): void
    {
        $service = new OrderWorkflowService();
        $commande = new CommandeProduit();

        $this->expectException(\InvalidArgumentException::class);
        $service->apply($commande, 'unknown');
    }

    public function testApplyDisallowedTransitionThrows(): void
    {
        $service = new OrderWorkflowService();
        $commande = new CommandeProduit();
        $commande->setStatut(StatutCommande::LIVREE);

        $this->expectException(\DomainException::class);
        $service->apply($commande, 'cancel');
    }
}
