<?php

namespace App\Repository;

use App\Entity\CommandeProduit;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeProduit>
 *
 * @method CommandeProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommandeProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommandeProduit[]    findAll()
 * @method CommandeProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduit::class);
    }

    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUtilisateur(Utilisateur $utilisateur, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findExpiredPendingReservations(\DateTimeInterface $cutoff): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->andWhere('c.dateCommande <= :cutoff')
            ->setParameter('status', \App\Enum\StatutCommande::EN_ATTENTE)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('c.dateCommande', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPastEtaWithoutPenalty(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deliveryEtaAt IS NOT NULL')
            ->andWhere('c.deliveryEtaAt < :now')
            ->andWhere('c.deliverySlaBreached = :breached')
            ->andWhere('c.statut NOT IN (:excludedStatuses)')
            ->setParameter('now', $now)
            ->setParameter('breached', false)
            ->setParameter('excludedStatuses', [
                \App\Enum\StatutCommande::LIVREE->value,
                \App\Enum\StatutCommande::ANNULEE->value,
            ])
            ->orderBy('c.deliveryEtaAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get orders within a date range with specific statuses.
     * 
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @param array $statuses Valid order statuses
     * @return CommandeProduit[] Array of orders
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, array $statuses = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.dateCommande BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('c.dateCommande', 'DESC');

        if (!empty($statuses)) {
            $qb->andWhere('c.statut IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all completed orders for analytics.
     * 
     * @return CommandeProduit[] Array of completed orders
     */
    public function findCompletedOrders(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut IN (:statuses)')
            ->setParameter('statuses', [
                \App\Enum\StatutCommande::LIVREE,
                \App\Enum\StatutCommande::CONFIRMEE,
            ])
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if an order exists for a specific product and customer.
     * 
     * @param Utilisateur $customer The customer
     * @param \App\Entity\Produit $product The product
     * @return bool True if customer has purchased this product
     */
    public function hasCustomerPurchasedProduct(Utilisateur $customer, \App\Entity\Produit $product): bool
    {
        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as count')
            ->innerJoin('c.lignesCommande', 'lc')
            ->where('c.utilisateur = :customer')
            ->andWhere('lc.produit = :product')
            ->setParameter('customer', $customer)
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }
}
