<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsDoctrineListener(event: Events::postPersist, priority: -100)]
#[AsDoctrineListener(event: Events::postUpdate, priority: -100)]
#[AsDoctrineListener(event: Events::preRemove, priority: -100)]
#[AsDoctrineListener(event: Events::postFlush, priority: 100)]
final class AdminDashboardMercureSubscriber
{
    private const TOPIC = 'https://mediconnect.local/admin/dashboard';

    private bool $needsPublish = false;

    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function postPersist(\Doctrine\ORM\Event\PostPersistEventArgs $args): void
    {
        if ($args->getObject() instanceof Utilisateur) {
            $this->needsPublish = true;
        }
    }

    public function postUpdate(\Doctrine\ORM\Event\PostUpdateEventArgs $args): void
    {
        if ($args->getObject() instanceof Utilisateur) {
            $this->needsPublish = true;
        }
    }

    public function preRemove(\Doctrine\ORM\Event\PreRemoveEventArgs $args): void
    {
        if ($args->getObject() instanceof Utilisateur) {
            $this->needsPublish = true;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->needsPublish) {
            $this->needsPublish = false;
            try {
                $this->hub->publish(new Update(self::TOPIC, json_encode(['refresh' => true])));
            } catch (\Throwable) {
                // Ignorer les erreurs Mercure (hub non démarré, etc.)
            }
        }
    }
}
