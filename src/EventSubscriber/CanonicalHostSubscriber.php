<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Normalisation d'hôte pour l'environnement local.
 *
 * Pour l'option A (Face ID fonctionnel), on laisse désormais
 * totalement tranquille localhost (PAS de redirection automatique
 * vers 127.0.0.1), afin que WebAuthn accepte le domaine.
 */
class CanonicalHostSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $appUrl
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 50],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // On ne force plus de redirection d'hôte.
        // Si tu ouvres http://localhost:8000, tu restes sur localhost.
        // Si tu ouvres http://127.0.0.1:8000, tu restes sur 127.0.0.1.
    }
}
