<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Get all notifications for current user (API endpoint)
     */
    #[Route('/api/list', name: 'app_notifications_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $notifications = $this->notificationService->getNotifications($user, 20);
        $unreadCount = $this->notificationService->compterNotificationsNonLues($user);

        $data = array_map(function(Notification $notif) {
            return [
                'id' => (string) $notif->getId(),
                'type' => $notif->getType(),
                'titre' => $notif->getTitre(),
                'message' => $notif->getMessage(),
                'lien' => $notif->getLien(),
                'lu' => $notif->isLu(),
                'dateCreation' => $notif->getDateCreation()->format('c'),
            ];
        }, $notifications);

        return $this->json([
            'notifications' => $data,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read
     */
    #[Route('/api/{id}/mark-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $notification = $this->entityManager->getRepository(Notification::class)->find((int) $id);

            if (!$notification) {
                return $this->json(['error' => 'Notification non trouvée'], 404);
            }

            $user = $this->getUser();
            if (!$user instanceof Utilisateur) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }

            // Check ownership
            if ($notification->getDestinataire() !== $user) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }

            $this->notificationService->marquerCommeLu($notification);

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark all notifications as read
     */
    #[Route('/api/mark-all-read', name: 'app_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $this->notificationService->marquerToutCommeLu($user);

        return $this->json(['success' => true]);
    }

    /**
     * Get unread count (for badge)
     */
    #[Route('/api/unread-count', name: 'app_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['count' => 0]);
        }
        $count = $this->notificationService->compterNotificationsNonLues($user);

        return $this->json(['count' => $count]);
    }

    /**
     * View all notifications page
     */
    #[Route('/', name: 'app_notifications_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        $notifications = $this->notificationService->getNotifications($user, 50);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}

