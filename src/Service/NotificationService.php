<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Entity\CategorieSante;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private HubInterface $hub,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * Notify admin when a doctor creates a new category
     */
    public function notifierAdminNouvelleCategorie(CategorieSante $categorie, Utilisateur $medecin): void
    {
        // Get all admins using discriminator
        $admins = $this->entityManager->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->where('u INSTANCE OF App\Entity\Admin')
            ->getQuery()
            ->getResult();

        foreach ($admins as $admin) {
            $notification = new Notification();
            $notification->setDestinataire($admin);
            $notification->setType('CATEGORIE_EN_ATTENTE');
            $notification->setTitre('Nouvelle catégorie à valider');
            $notification->setMessage(
                sprintf(
                    'Le Dr. %s a créé une nouvelle catégorie "%s" qui nécessite votre validation.',
                    $medecin->getNomComplet(),
                    $categorie->getNom()
                )
            );
            $notification->setLien($this->urlGenerator->generate('app_admin_categories_en_attente'));
            $notification->setCategorieId($categorie->getId());

            $this->entityManager->persist($notification);
            
            // Send real-time notification via Mercure
            $this->envoyerNotificationTempsReel($admin, $notification);
        }

        $this->entityManager->flush();
    }

    /**
     * Notify doctor when their category is approved
     */
    public function notifierMedecinCategorieApprouvee(CategorieSante $categorie, Utilisateur $admin): void
    {
        $medecin = $categorie->getCreePar();
        
        if (!$medecin) {
            return;
        }

        $notification = new Notification();
        $notification->setDestinataire($medecin);
        $notification->setType('CATEGORIE_APPROUVEE');
        $notification->setTitre('Catégorie approuvée');
        $notification->setMessage(
            sprintf(
                'Votre catégorie "%s" a été approuvée par %s et est maintenant visible par les patients.',
                $categorie->getNom(),
                $admin->getNomComplet()
            )
        );
        $notification->setLien($this->urlGenerator->generate('app_savoir_medical_categorie', ['id' => $categorie->getId()]));
        $notification->setCategorieId($categorie->getId());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Send real-time notification via Mercure
        $this->envoyerNotificationTempsReel($medecin, $notification);
    }

    /**
     * Notify doctor when their category is rejected
     */
    public function notifierMedecinCategorieRejetee(CategorieSante $categorie, Utilisateur $admin): void
    {
        $medecin = $categorie->getCreePar();
        
        if (!$medecin) {
            return;
        }

        $notification = new Notification();
        $notification->setDestinataire($medecin);
        $notification->setType('CATEGORIE_REJETEE');
        $notification->setTitre('Catégorie rejetée');
        $notification->setMessage(
            sprintf(
                'Votre catégorie "%s" a été rejetée par %s. Veuillez la réviser et la soumettre à nouveau.',
                $categorie->getNom(),
                $admin->getNomComplet()
            )
        );
        $notification->setLien($this->urlGenerator->generate('app_savoir_medical_index'));
        $notification->setCategorieId($categorie->getId());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Send real-time notification via Mercure
        $this->envoyerNotificationTempsReel($medecin, $notification);
    }

    /**
     * Notify all patients when a new category is published
     */
    public function notifierPatientsNouvelleCategorie(CategorieSante $categorie): void
    {
        // Get all patients using discriminator
        $patients = $this->entityManager->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->where('u INSTANCE OF App\Entity\Patient')
            ->getQuery()
            ->getResult();

        foreach ($patients as $patient) {
            $notification = new Notification();
            $notification->setDestinataire($patient);
            $notification->setType('NOUVELLE_CATEGORIE');
            $notification->setTitre('Nouveau contenu éducatif disponible');
            $notification->setMessage(
                sprintf(
                    'Une nouvelle catégorie "%s" (%s) est maintenant disponible. Découvrez de nouveaux cours et quiz !',
                    $categorie->getNom(),
                    $categorie->getType()
                )
            );
            $notification->setLien($this->urlGenerator->generate('app_savoir_medical_categorie', ['id' => $categorie->getId()]));
            $notification->setCategorieId($categorie->getId());

            $this->entityManager->persist($notification);
            
            // Send real-time notification via Mercure (batch for performance)
            if (count($patients) < 100) { // Only send real-time for small batches
                $this->envoyerNotificationTempsReel($patient, $notification);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Send real-time notification via Mercure
     */
    private function envoyerNotificationTempsReel(Utilisateur $user, Notification $notification): void
    {
        try {
            $userId = $user->getId(); // Integer ID
            $update = new Update(
                sprintf('notifications/%s', $userId),
                json_encode([
                    'id' => (string) $notification->getId(),
                    'type' => $notification->getType(),
                    'titre' => $notification->getTitre(),
                    'message' => $notification->getMessage(),
                    'lien' => $notification->getLien(),
                    'dateCreation' => $notification->getDateCreation()->format('c'),
                ])
            );

            $this->hub->publish($update);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            error_log('Mercure notification error: ' . $e->getMessage());
        }
    }

    /**
     * Get unread notifications for a user
     */
    public function getNotificationsNonLues(Utilisateur $user): array
    {
        return $this->notificationRepository->findUnreadByUser($user);
    }

    /**
     * Get all notifications for a user
     */
    public function getNotifications(Utilisateur $user, int $limit = 50): array
    {
        return $this->notificationRepository->findByUser($user, $limit);
    }

    /**
     * Count unread notifications
     */
    public function compterNotificationsNonLues(Utilisateur $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }

    /**
     * Mark notification as read
     */
    public function marquerCommeLu(Notification $notification): void
    {
        $notification->setLu(true);
        $this->entityManager->flush();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function marquerToutCommeLu(Utilisateur $user): void
    {
        $this->notificationRepository->markAllAsReadForUser($user);
    }
}
