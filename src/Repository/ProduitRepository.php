<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findBySearchTerm(string $term): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :term')
            ->orWhere('p.description LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategorie(int $categorieId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorieId')
            ->setParameter('categorieId', $categorieId)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
