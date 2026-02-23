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

    /**
     * Get products frequently bought together (co-purchase analysis).
     * 
     * @param Produit $product Reference product
     * @param int $limit Maximum number of results
     * @return array<string, mixed> Array of co-purchased products
     */
    public function findCoProductsForProduct(Produit $product, int $limit = 5): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.nom, p.prix, p.image, COUNT(lc2.id) as frequency')
            ->from('App\Entity\LigneCommande', 'lc1')
            ->innerJoin('lc1.commande', 'c1')
            ->innerJoin('App\Entity\LigneCommande', 'lc2', 'WITH', 'c1.id = lc2.commande')
            ->innerJoin('lc2.produit', 'p', 'WITH', 'p.id = lc2.produit')
            ->where('lc1.produit = :product')
            ->andWhere('p.id != :productId')
            ->setParameter('product', $product)
            ->setParameter('productId', $product->getId())
            ->groupBy('p.id')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top-selling products.
     * 
     * @param int $limit Maximum number of results
     * @return array Product statistics
     */
    public function findTopSellers(int $limit = 10): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.nom, p.prix, p.image, SUM(lc.quantite) as totalSold')
            ->from('App\Entity\LigneCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get trending products (recent sales).
     * 
     * @param int $limit Maximum number of results
     * @param int $daysBack Number of days to look back
     * @return array Product statistics
     */
    public function findTrendingProducts(int $limit = 5, int $daysBack = 7): array
    {
        $dateFrom = (new \DateTime())->modify("-{$daysBack} days");

        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.nom, p.prix, p.image, SUM(lc.quantite) as recentSales')
            ->from('App\Entity\LigneCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->innerJoin('lc.commande', 'c')
            ->where('c.dateCommande >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->groupBy('p.id')
            ->orderBy('recentSales', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products not yet purchased by a customer.
     * 
     * @param \App\Entity\Utilisateur $customer The customer
     * @param int $limit Maximum number of results
     * @return Produit[] Array of products
     */
    public function findUnpurchasedByCustomer(\App\Entity\Utilisateur $customer, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->andWhere('p.id NOT IN (
                SELECT DISTINCT lc.produit
                FROM App\Entity\LigneCommande lc
                WHERE lc.commande IN (
                    SELECT c.id FROM App\Entity\CommandeProduit c
                    WHERE c.utilisateur = :customerId
                )
            )')
            ->setParameter('customerId', $customer->getId())
            ->orderBy('p.nom', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get product sales statistics.
     * 
     * @return array Product statistics
     */
    public function findProductSalesStatistics(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.nom, COUNT(lc.id) as orderCount, SUM(lc.quantite) as totalQuantity, 
                     SUM(CAST(lc.prixUnitaire as FLOAT) * lc.quantite) as totalRevenue')
            ->from('App\Entity\Produit', 'p')
            ->leftJoin('p.lignesCommande', 'lc')
            ->groupBy('p.id')
            ->orderBy('totalRevenue', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
