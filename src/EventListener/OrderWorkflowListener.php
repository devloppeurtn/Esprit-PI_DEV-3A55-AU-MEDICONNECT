<?php

namespace App\EventListener;

use App\Entity\CommandeProduit;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Listener for order workflow state machine events.
 * Provides audit logging, guards, and business logic for order transitions.
 */
#[AsEventListener(event: 'workflow.order_workflow.guard', method: 'onGuardTransition')]
#[AsEventListener(event: 'workflow.order_workflow.enter', method: 'onEnterPlace')]
#[AsEventListener(event: 'workflow.order_workflow.leave', method: 'onLeavePlace')]
#[AsEventListener(event: 'workflow.order_workflow.completed', method: 'onTransitionCompleted')]
class OrderWorkflowListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Guard against invalid transitions with business rules.
     * This prevents invalid state transitions before they happen.
     */
    public function onGuardTransition(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof CommandeProduit) {
            return;
        }

        $transitionName = $event->getTransition()->getName();

        // Rule 1: Cannot validate order if stock is not available
        if ($transitionName === 'validate') {
            if (!$this->hasRequiredStock($order)) {
                $event->setBlocked(true, 'Stock insuffisant pour cette commande');
                $this->logger->warning('Transition bloquée: Stock insuffisant', [
                    'order_id' => $order->getId(),
                    'transition' => $transitionName,
                ]);
            }
        }

        // Rule 2: Cannot prepare order if payment not confirmed
        if ($transitionName === 'prepare') {
            if ($order->getModePaiement() === null) {
                $event->setBlocked(true, 'Mode de paiement non défini');
                $this->logger->warning('Transition bloquée: Paiement non défini', [
                    'order_id' => $order->getId(),
                ]);
            }
        }

        // Rule 3: Cannot ship order if no delivery address
        if ($transitionName === 'ship') {
            if (empty($order->getAdresseLivraison())) {
                $event->setBlocked(true, 'Adresse de livraison manquante');
                $this->logger->warning('Transition bloquée: Adresse manquante', [
                    'order_id' => $order->getId(),
                ]);
            }
        }

        // Audit: Log all guard checks
        $this->logger->info('Workflow guard check', [
            'order_id' => $order->getId(),
            'transition' => $transitionName,
            'blocked' => $event->isBlocked(),
        ]);
    }

    /**
     * Execute actions when entering a new place (state).
     */
    public function onEnterPlace(Event $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof CommandeProduit) {
            return;
        }

        $placeName = $event->getPlace()->getName();

        // Action: When entering VALIDEE - lock stock
        if ($placeName === 'VALIDEE') {
            $this->logger->info('Order validated - stock locked', [
                'order_id' => $order->getId(),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        }

        // Action: When entering PREPAREE - trigger warehouse
        if ($placeName === 'PREPAREE') {
            $this->logger->info('Order ready for preparation', [
                'order_id' => $order->getId(),
                'address' => $order->getAdresseLivraison(),
            ]);
            // TODO: Send to warehouse management system
        }

        // Action: When entering LIVREE - notify customer
        if ($placeName === 'LIVREE') {
            $this->logger->info('Order shipped - customer notified', [
                'order_id' => $order->getId(),
                'customer_email' => $order->getUtilisateur()?->getEmail(),
            ]);
            // TODO: Send delivery confirmation email
        }

        // Action: When entering ANNULEE - release stock
        if ($placeName === 'ANNULEE') {
            $this->logger->warning('Order cancelled - stock released', [
                'order_id' => $order->getId(),
                'lines_count' => count($order->getLignesCommande()),
            ]);
            // TODO: Update stock levels
        }
    }

    /**
     * Audit logging when leaving a state.
     */
    public function onLeavePlace(Event $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof CommandeProduit) {
            return;
        }

        $placeName = $event->getPlace()->getName();

        $this->logger->info('Order state transition - leaving place', [
            'order_id' => $order->getId(),
            'from_state' => $placeName,
        ]);
    }

    /**
     * Audit logging after successful transition.
     */
    public function onTransitionCompleted(Event $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof CommandeProduit) {
            return;
        }

        $transitionName = $event->getTransition()->getName();
        $fromPlaces = $event->getTransition()->getFroms();
        $toPlaces = $event->getTransition()->getTos();

        $this->logger->info('Workflow transition completed', [
            'order_id' => $order->getId(),
            'transition' => $transitionName,
            'from' => implode(', ', $fromPlaces),
            'to' => implode(', ', $toPlaces),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'user_id' => null, // TODO: Inject security token to get current user
        ]);

        // Store audit trail in database
        // TODO: Create OrderAuditLog entity and save transition
    }

    /**
     * Check if order has required stock.
     */
    private function hasRequiredStock(CommandeProduit $order): bool
    {
        // TODO: Implement actual stock check logic
        // For now, return true (would check each LigneCommande against Produit stock)
        return true;
    }
}
