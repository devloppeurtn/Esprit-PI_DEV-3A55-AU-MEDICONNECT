<?php

namespace App\Repository;

use App\Entity\CategorieSante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieSante>
 */
class CategorieSanteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieSante::class);
    }

    /**
     * Find categories by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all categories with their courses count
     */
    public function findAllWithCoursCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(co.id) as coursCount')
            ->leftJoin('c.coursEducatifs', 'co')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search categories by name or description
     */
    public function search(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom LIKE :search OR c.description LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
