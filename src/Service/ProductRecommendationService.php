<?php

namespace App\Service;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\LigneCommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for providing product recommendations based on purchase history.
 * Implements "Customers who bought this also bought..." functionality.
 */
class ProductRecommendationService
{
    public function __construct(
        private LigneCommandeRepository $ligneCommandeRepository,
        private ProduitRepository $produitRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Get products frequently bought together with a specific product.
     * 
     * @param Produit $produit The reference product
     * @param int $limit Maximum number of recommendations to return
     * @return array<Produit> Array of recommended products
     */
    public function getProductsBoughtTogether(Produit $produit, int $limit = 5): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $results = $qb
            ->select('p.id, p.nom, p.prix, p.image, COUNT(lc2.id) as frequency')
            ->from('App\Entity\LigneCommande', 'lc1')
            ->innerJoin('lc1.commande', 'c1')
            ->innerJoin('App\Entity\LigneCommande', 'lc2', 'WITH', 'c1.id = lc2.commande')
            ->innerJoin('lc2.produit', 'p', 'WITH', 'p.id = lc2.produit')
            ->where('lc1.produit = :produit')
            ->andWhere('p.id != :produitId')
            ->setParameter('produit', $produit)
            ->setParameter('produitId', $produit->getId())
            ->groupBy('p.id')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Convert results to Produit entities
        return $this->convertResultsToProduits($results, $limit);
    }

    /**
     * Get personalized recommendations for a customer based on their purchase history.
     * 
     * @param Utilisateur $customer The customer
     * @param int $limit Maximum number of recommendations
     * @return array<Produit> Array of recommended products
     */
    public function getRecommendationsForCustomer(Utilisateur $customer, int $limit = 5): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        // Get products that other customers with similar purchases have bought
        $results = $qb
            ->select('p.id, p.nom, p.prix, p.image, COUNT(DISTINCT lc.id) as frequency')
            ->from('App\Entity\CommandeProduit', 'c1')
            ->innerJoin('c1.lignesCommande', 'lc1')
            ->innerJoin('c1.utilisateur', 'u1')
            ->innerJoin('App\Entity\CommandeProduit', 'c2')
            ->innerJoin('c2.lignesCommande', 'lc2')
            ->innerJoin('lc2.produit', 'p', 'WITH', 'lc2.produit = p.id')
            ->where('u1.id = :customerId')
            ->andWhere('lc1.produit IN (
                SELECT DISTINCT lc3.produit
                FROM App\Entity\LigneCommande lc3
                WHERE lc3.commande IN (
                    SELECT c3.id FROM App\Entity\CommandeProduit c3
                    WHERE c3.utilisateur = :customerId
                )
            )')
            ->andWhere('p.id NOT IN (
                SELECT DISTINCT lc4.produit
                FROM App\Entity\LigneCommande lc4
                WHERE lc4.commande IN (
                    SELECT c4.id FROM App\Entity\CommandeProduit c4
                    WHERE c4.utilisateur = :customerId
                )
            )')
            ->setParameter('customerId', $customer->getId())
            ->groupBy('p.id')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->convertResultsToProduits($results, $limit);
    }

    /**
     * Get products from the same category as a purchased product.
     * 
     * @param Produit $produit The reference product
     * @param int $limit Maximum number of recommendations
     * @return array<Produit> Array of recommended products
     */
    public function getRelatedProductsByCategory(Produit $produit, int $limit = 5): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Produit', 'p')
            ->where('p.categorie = :categorie')
            ->andWhere('p.id != :produitId')
            ->andWhere('p.stock > 0')
            ->setParameter('categorie', $produit->getCategorie())
            ->setParameter('produitId', $produit->getId())
            ->orderBy('p.nom', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top-selling products that could be recommended to any customer.
     * 
     * @param int $limit Maximum number of recommendations
     * @return array<Produit> Array of top-selling products
     */
    public function getTopSellingProducts(int $limit = 10): array
    {
        $results = $this->entityManager->createQueryBuilder()
            ->select('p.id, p.nom, p.prix, p.image, SUM(lc.quantite) as totalSold')
            ->from('App\Entity\LigneCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->convertResultsToProduits($results, $limit);
    }

    /**
     * Get trending products - products with recent high sales volume.
     * 
     * @param int $limit Maximum number of recommendations
     * @param int $daysBack Number of days to look back
     * @return array<Produit> Array of trending products
     */
    public function getTrendingProducts(int $limit = 5, int $daysBack = 7): array
    {
        $dateFrom = (new \DateTime())->modify("-{$daysBack} days");

        $results = $this->entityManager->createQueryBuilder()
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

        return $this->convertResultsToProduits($results, $limit);
    }

    /**
     * Get products with high ratings that the customer hasn't bought yet.
     * 
     * @param Utilisateur $customer The customer
     * @param float $minRating Minimum average rating
     * @param int $limit Maximum number of recommendations
     * @return array<Produit> Array of highly-rated products
     */
    public function getHighRatedProducts(Utilisateur $customer, float $minRating = 4.0, int $limit = 5): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Produit', 'p')
            ->leftJoin('p.avisProduits', 'a')
            ->where('p.stock > 0')
            ->andWhere('p.id NOT IN (
                SELECT DISTINCT lc.produit
                FROM App\Entity\LigneCommande lc
                WHERE lc.commande IN (
                    SELECT c.id FROM App\Entity\CommandeProduit c
                    WHERE c.utilisateur = :customerId
                )
            )')
            ->groupBy('p.id')
            ->having('AVG(a.note) >= :minRating')
            ->setParameter('minRating', $minRating)
            ->setParameter('customerId', $customer->getId())
            ->orderBy('AVG(a.note)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Convert query results to Produit entities.
     * 
     * @param array $results Array of results from query
     * @param int $limit Limit for results
     * @return array<Produit> Array of Produit entities
     */
    private function convertResultsToProduits(array $results, int $limit): array
    {
        if (empty($results)) {
            return [];
        }

        $ids = array_map(fn($r) => $r['id'] ?? $r[0], $results);
        
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Produit', 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
