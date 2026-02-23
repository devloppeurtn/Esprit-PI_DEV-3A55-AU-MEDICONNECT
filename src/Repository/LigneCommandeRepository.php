<?php

namespace App\Repository;

use App\Entity\LigneCommande;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneCommande>
 *
 * @method LigneCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method LigneCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method LigneCommande[]    findAll()
 * @method LigneCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LigneCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneCommande::class);
    }

    /**
     * @return array<int, array{product_id:int, sale_date:string, qty:string|int|float}>
     */
    public function findDailySalesByProduct(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        array $validStatuses
    ): array {
        if ($validStatuses === []) {
            return [];
        }

        $sql = <<<SQL
SELECT
    lc.produit_id AS product_id,
    DATE(cp.date_commande) AS sale_date,
    SUM(lc.quantite) AS qty
FROM ligne_commande lc
INNER JOIN commande_produit cp ON cp.id = lc.commande_id
WHERE cp.date_commande BETWEEN :from AND :to
  AND cp.statut IN (:statuses)
GROUP BY lc.produit_id, DATE(cp.date_commande)
SQL;

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $sql,
                [
                    'from' => $from->format('Y-m-d H:i:s'),
                    'to' => $to->format('Y-m-d H:i:s'),
                    'statuses' => array_values($validStatuses),
                ],
                [
                    'statuses' => ArrayParameterType::STRING,
                ]
            )
            ->fetchAllAssociative();
    }

    /**
     * Get customer purchase frequency data.
     * 
     * @param \App\Entity\Utilisateur $customer The customer
     * @return LigneCommande[] Array of line items
     */
    public function findByCustomer(\App\Entity\Utilisateur $customer): array
    {
        return $this->createQueryBuilder('lc')
            ->innerJoin('lc.commande', 'c')
            ->where('c.utilisateur = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get product purchase frequency (how many times a product was ordered).
     * 
     * @return array Product frequency statistics
     */
    public function findProductPurchaseFrequency(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.nom, COUNT(lc.id) as purchaseCount, SUM(lc.quantite) as totalQuantity')
            ->from('App\Entity\LigneCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->innerJoin('lc.commande', 'c')
            ->groupBy('p.id, p.nom')
            ->orderBy('purchaseCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get co-purchase associations (which products are frequently bought together).
     * 
     * @param int $limit Maximum associations to return
     * @return array Co-purchase data
     */
    public function findCoPurchaseAssociations(int $limit = 20): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('
                p1.id as product1Id,
                p1.nom as product1Name,
                p2.id as product2Id,
                p2.nom as product2Name,
                COUNT(DISTINCT lc1.commande) as frequency
            ')
            ->from('App\Entity\LigneCommande', 'lc1')
            ->innerJoin('lc1.commande', 'c')
            ->innerJoin('lc1.produit', 'p1')
            ->innerJoin('App\Entity\LigneCommande', 'lc2', 'WITH', 'c.id = lc2.commande')
            ->innerJoin('lc2.produit', 'p2', 'WITH', 'p1.id < p2.id')
            ->groupBy('p1.id, p1.nom, p2.id, p2.nom')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get revenue by product.
     * 
     * @param int $limit Maximum results
     * @return array Product revenue statistics
     */
    public function findRevenueByProduct(int $limit = 20): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('
                p.id,
                p.nom,
                SUM(CAST(lc.prixUnitaire as FLOAT) * lc.quantite) as totalRevenue,
                SUM(lc.quantite) as totalQuantity
            ')
            ->from('App\Entity\LigneCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->groupBy('p.id, p.nom')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
