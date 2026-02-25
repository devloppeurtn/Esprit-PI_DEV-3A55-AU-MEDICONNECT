<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[]
     */
    public function findUnreadByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.utilisateur = :user')
            ->andWhere('n.estLu = :val')
            ->setParameter('user', $user)
            ->setParameter('val', false)
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Notification[]
     */
    public function findLatestByUser(Utilisateur $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('n.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUser(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.utilisateur = :user')
            ->andWhere('n.estLu = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadForUser(Utilisateur $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.estLu', ':isRead')
            ->where('n.utilisateur = :user')
            ->andWhere('n.estLu = :currentlyUnread')
            ->setParameter('isRead', true)
            ->setParameter('currentlyUnread', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function existsByUserTitleAndMessage(Utilisateur $user, string $titre, string $message): bool
    {
        if (trim($titre) === '' || trim($message) === '') {
            return false;
        }

        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.utilisateur = :user')
            ->andWhere('n.titre = :titre')
            ->andWhere('n.message = :message')
            ->setParameter('user', $user)
            ->setParameter('titre', $titre)
            ->setParameter('message', $message)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
