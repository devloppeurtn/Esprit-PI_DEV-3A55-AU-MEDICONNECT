<?php

namespace App\EventSubscriber;

use App\Service\StockReservationService;
use App\Service\DeliverySlaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StockReservationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StockReservationService $stockReservationService,
        private DeliverySlaService $deliverySlaService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = (string) $event->getRequest()->getPathInfo();
        if (\str_starts_with($path, '/_')) {
            return;
        }

        $this->stockReservationService->releaseExpiredReservations();
        $this->deliverySlaService->applyDelayPenalties();
    }
}
