<?php

namespace App\Repository;

use App\Entity\AvisProduit;
use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvisProduit>
 */
class AvisProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisProduit::class);
    }

    public function findByProduitOrdered(Produit $produit): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.produit = :produit')
            ->setParameter('produit', $produit)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageForProduit(Produit $produit): ?float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as avgNote')
            ->andWhere('a.produit = :produit')
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return null;
        }
        return round((float) $result, 1);
    }
}
