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
}
