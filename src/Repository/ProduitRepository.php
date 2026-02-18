<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 *
 * @method Produit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Produit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Produit[]    findAll()
 * @method Produit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findBySearchTerm(string $term): array
    {
        return $this->findByFilters([
            'q' => $term,
            'sort' => 'name_asc',
        ]);
    }

    public function findByCategorie(int $categorieId): array
    {
        return $this->findByFilters([
            'categorieId' => $categorieId,
            'sort' => 'name_asc',
        ]);
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{
     *     q?: string,
     *     categorieId?: int|null,
     *     minPrice?: string|float|int|null,
     *     maxPrice?: string|float|int|null,
     *     inStock?: bool,
     *     sort?: string
     * } $filters
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c');

        $searchTerm = trim((string) ($filters['q'] ?? ''));
        if ($searchTerm !== '') {
            $qb
                ->andWhere('p.nom LIKE :term OR p.description LIKE :term')
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        $categorieId = $filters['categorieId'] ?? null;
        if ($categorieId !== null && $categorieId > 0) {
            $qb
                ->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        $minPrice = $filters['minPrice'] ?? null;
        if ($minPrice !== null && $minPrice !== '') {
            $qb
                ->andWhere('p.prix >= :minPrice')
                ->setParameter('minPrice', (string) $minPrice);
        }

        $maxPrice = $filters['maxPrice'] ?? null;
        if ($maxPrice !== null && $maxPrice !== '') {
            $qb
                ->andWhere('p.prix <= :maxPrice')
                ->setParameter('maxPrice', (string) $maxPrice);
        }

        if (!empty($filters['inStock'])) {
            $qb->andWhere('p.stock > 0');
        }

        $sort = (string) ($filters['sort'] ?? 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $qb->orderBy('p.nom', 'DESC');
                break;
            case 'price_asc':
                $qb->orderBy('p.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.prix', 'DESC');
                break;
            case 'stock_desc':
                $qb->orderBy('p.stock', 'DESC');
                break;
            case 'newest':
                $qb->orderBy('p.id', 'DESC');
                break;
            default:
                $qb->orderBy('p.nom', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }
}
