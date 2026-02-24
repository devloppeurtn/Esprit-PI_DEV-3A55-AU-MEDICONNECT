<?php

namespace App\Repository;

use App\Entity\CategorieProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieProduit>
 *
 * @method CategorieProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieProduit[]    findAll()
 * @method CategorieProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieProduit::class);
    }
}
