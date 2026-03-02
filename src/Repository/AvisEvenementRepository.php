<?php

namespace App\Repository;

use App\Entity\AvisEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvisEvenement>
 *
 * @method AvisEvenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvisEvenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvisEvenement[]    findAll()
 * @method AvisEvenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvisEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisEvenement::class);
    }
}
