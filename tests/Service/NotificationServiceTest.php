<?php

namespace App\Tests\Service;

use App\Entity\Admin;
use App\Entity\CategorieSante;
use App\Entity\Medecin;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationServiceTest extends TestCase
{
    public function testMarquerCommeLuUpdatesNotificationAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(NotificationRepository::class);
        $hub = $this->createMock(HubInterface::class);
        $url = $this->createMock(UrlGeneratorInterface::class);

        $em->expects($this->once())->method('flush');

        $service = new NotificationService($em, $repo, $hub, $url);
        $notification = new Notification();

        $this->assertFalse($notification->isLu());
        $service->marquerCommeLu($notification);

        $this->assertTrue($notification->isLu());
        $this->assertNotNull($notification->getDateLecture());
    }

    public function testNotifierMedecinCategorieApprouveeCreatesNotificationWithLink(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(NotificationRepository::class);
        $hub = $this->createMock(HubInterface::class);
        $url = $this->createMock(UrlGeneratorInterface::class);

        $url->method('generate')->willReturn('/fake-link');

        $hub->expects($this->once())->method('publish')->with($this->isInstanceOf(Update::class));
        $em->expects($this->once())->method('persist')->with($this->callback(function (Notification $n) {
            return $n->getLien() === '/fake-link'
                && $n->getType() === 'CATEGORIE_APPROUVEE'
                && $n->getDestinataire() instanceof Medecin;
        }));
        $em->expects($this->once())->method('flush');

        $service = new NotificationService($em, $repo, $hub, $url);

        $admin = (new Admin())->setNomComplet('Admin Test')->setEmail('admin@test.local');
        $medecin = (new Medecin())->setNomComplet('Dr Test')->setEmail('medecin@test.local');

        $categorie = (new CategorieSante())
            ->setNom('Cardio')
            ->setType('Specialite')
            ->setCreePar($medecin);

        $service->notifierMedecinCategorieApprouvee($categorie, $admin);
    }

    public function testCompterNotificationsNonLuesUsesRepository(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(NotificationRepository::class);
        $hub = $this->createMock(HubInterface::class);
        $url = $this->createMock(UrlGeneratorInterface::class);

        $repo->expects($this->once())->method('countUnreadByUser')->willReturn(3);

        $service = new NotificationService($em, $repo, $hub, $url);
        $user = (new Medecin())->setNomComplet('User Test')->setEmail('user@test.local');

        $this->assertSame(3, $service->compterNotificationsNonLues($user));
    }
}
