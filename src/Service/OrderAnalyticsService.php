<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for analyzing customer behavior and order patterns.
 * Provides insights for customer segmentation and personalized marketing.
 */
class OrderAnalyticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Get comprehensive order analytics for a customer.
     * 
     * @param Utilisateur $customer The customer
     * @return array<string, mixed> Customer analytics data
     */
    public function getCustomerAnalytics(Utilisateur $customer): array
    {
        return [
            'totalSpent' => $this->getTotalSpent($customer),
            'orderCount' => $this->getOrderCount($customer),
            'averageOrderValue' => $this->getAverageOrderValue($customer),
            'orderFrequency' => $this->getOrderFrequency($customer),
            'preferredCategories' => $this->getPreferredCategories($customer, 5),
            'ordersOverTime' => $this->getOrdersOverTime($customer, 12),
            'isRepeatCustomer' => $this->isRepeatCustomer($customer),
            'lastOrderDate' => $this->getLastOrderDate($customer),
            'firstOrderDate' => $this->getFirstOrderDate($customer),
            'lifespan' => $this->getCustomerLifespan($customer),
            'mostPurchasedProducts' => $this->getMostPurchasedProducts($customer, 5),
            'customerSegment' => $this->getCustomerSegment($customer),
            'spendingTrend' => $this->getSpendingTrend($customer),
        ];
    }

    /**
     * Get total amount spent by a customer.
     * 
     * @param Utilisateur $customer The customer
     * @return float|string Total spent (as decimal)
     */
    public function getTotalSpent(Utilisateur $customer): float|string
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('SUM(c.montantTotal) as total')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.statut IN (:validStatuses)')
            ->setParameter('customer', $customer)
            ->setParameter('validStatuses', [
                StatutCommande::LIVREE,
                StatutCommande::CONFIRMEE,
            ])
            ->getQuery()
            ->getOneOrNullResult();

        return $result['total'] ?? 0;
    }

    /**
     * Get total number of orders placed by a customer.
     * 
     * @param Utilisateur $customer The customer
     * @return int Order count
     */
    public function getOrderCount(Utilisateur $customer): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get average order value for a customer.
     * 
     * @param Utilisateur $customer The customer
     * @return float|string Average order value
     */
    public function getAverageOrderValue(Utilisateur $customer): float|string
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('AVG(c.montantTotal) as average')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.montantTotal IS NOT NULL')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['average'] ?? 0;
    }

    /**
     * Calculate how frequently a customer orders (average days between orders).
     * 
     * @param Utilisateur $customer The customer
     * @return float|int Average days between orders (-1 if less than 2 orders)
     */
    public function getOrderFrequency(Utilisateur $customer): float|int
    {
        $orders = $this->entityManager->createQueryBuilder()
            ->select('c.dateCommande')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->orderBy('c.dateCommande', 'ASC')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult();

        if (count($orders) < 2) {
            return -1;
        }

        $firstOrder = $orders[0]['dateCommande'];
        $lastOrder = $orders[count($orders) - 1]['dateCommande'];
        
        $daysSpan = $firstOrder->diff($lastOrder)->days;
        $frequency = $daysSpan / (count($orders) - 1);

        return round($frequency, 2);
    }

    /**
     * Get preferred product categories for a customer.
     * 
     * @param Utilisateur $customer The customer
     * @param int $limit Number of top categories to return
     * @return array<string, mixed> Array of categories with purchase count
     */
    public function getPreferredCategories(Utilisateur $customer, int $limit = 5): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cat.id, cat.nom as categoryName, COUNT(lc.id) as purchaseCount, SUM(lc.quantite) as totalQuantity')
            ->from('App\Entity\CommandeProduit', 'c')
            ->innerJoin('c.lignesCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->innerJoin('p.categorie', 'cat')
            ->where('c.utilisateur = :customer')
            ->setParameter('customer', $customer)
            ->groupBy('cat.id, cat.nom')
            ->orderBy('purchaseCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get order history with monthly breakdown.
     * 
     * @param Utilisateur $customer The customer
     * @param int $months Number of months to look back
     * @return array<string, mixed> Orders grouped by month
     */
    public function getOrdersOverTime(Utilisateur $customer, int $months = 12): array
    {
        $dateFrom = (new \DateTime())->modify("-{$months} months");

        $results = $this->entityManager->createQueryBuilder()
            ->select('
                DATE_FORMAT(c.dateCommande, \'%Y-%m\') as month,
                COUNT(c.id) as orderCount,
                SUM(c.montantTotal) as monthlyRevenue,
                AVG(c.montantTotal) as avgOrderValue
            ')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.dateCommande >= :dateFrom')
            ->setParameter('customer', $customer)
            ->setParameter('dateFrom', $dateFrom)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(function($result) {
            return [
                'month' => $result['month'],
                'orderCount' => (int) $result['orderCount'],
                'monthlyRevenue' => $result['monthlyRevenue'],
                'avgOrderValue' => $result['avgOrderValue'],
            ];
        }, $results);
    }

    /**
     * Determine if a customer is a repeat customer (more than one order).
     * 
     * @param Utilisateur $customer The customer
     * @return bool True if repeat customer
     */
    public function isRepeatCustomer(Utilisateur $customer): bool
    {
        return $this->getOrderCount($customer) > 1;
    }

    /**
     * Get the date of the customer's last order.
     * 
     * @param Utilisateur $customer The customer
     * @return \DateTimeInterface|null Last order date
     */
    public function getLastOrderDate(Utilisateur $customer): ?\DateTimeInterface
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('c.dateCommande')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults(1)
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['dateCommande'] ?? null;
    }

    /**
     * Get the date of the customer's first order.
     * 
     * @param Utilisateur $customer The customer
     * @return \DateTimeInterface|null First order date
     */
    public function getFirstOrderDate(Utilisateur $customer): ?\DateTimeInterface
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('c.dateCommande')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->orderBy('c.dateCommande', 'ASC')
            ->setMaxResults(1)
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['dateCommande'] ?? null;
    }

    /**
     * Get customer lifespan (days since first order).
     * 
     * @param Utilisateur $customer The customer
     * @return int Days since first order
     */
    public function getCustomerLifespan(Utilisateur $customer): int
    {
        $firstOrder = $this->getFirstOrderDate($customer);
        if (!$firstOrder) {
            return 0;
        }

        return (new \DateTime())->diff($firstOrder)->days;
    }

    /**
     * Get top purchased products by a customer.
     * 
     * @param Utilisateur $customer The customer
     * @param int $limit Number of top products
     * @return array<string, mixed> Array of products with quantity
     */
    public function getMostPurchasedProducts(Utilisateur $customer, int $limit = 5): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p.id, p.nom, p.image, SUM(lc.quantite) as totalQuantity, COUNT(DISTINCT c.id) as orderCount')
            ->from('App\Entity\CommandeProduit', 'c')
            ->innerJoin('c.lignesCommande', 'lc')
            ->innerJoin('lc.produit', 'p')
            ->where('c.utilisateur = :customer')
            ->setParameter('customer', $customer)
            ->groupBy('p.id, p.nom, p.image')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Segment customer based on spending patterns.
     * 
     * @param Utilisateur $customer The customer
     * @return string Customer segment (Gold, Silver, Bronze, Regular)
     */
    public function getCustomerSegment(Utilisateur $customer): string
    {
        $totalSpent = (float) $this->getTotalSpent($customer);

        if ($totalSpent >= 5000) {
            return 'Gold';
        } elseif ($totalSpent >= 2000) {
            return 'Silver';
        } elseif ($totalSpent >= 500) {
            return 'Bronze';
        }

        return 'Regular';
    }

    /**
     * Get spending trend for a customer (increasing, stable, decreasing).
     * 
     * @param Utilisateur $customer The customer
     * @return string Spending trend
     */
    public function getSpendingTrend(Utilisateur $customer): string
    {
        $sixMonthsAgo = (new \DateTime())->modify('-6 months');
        $threeMonthsAgo = (new \DateTime())->modify('-3 months');

        $firstHalf = (float) $this->entityManager->createQueryBuilder()
            ->select('SUM(c.montantTotal)')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.dateCommande BETWEEN :sixMonthsAgo AND :threeMonthsAgo')
            ->setParameter('customer', $customer)
            ->setParameter('sixMonthsAgo', $sixMonthsAgo)
            ->setParameter('threeMonthsAgo', $threeMonthsAgo)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $secondHalf = (float) $this->entityManager->createQueryBuilder()
            ->select('SUM(c.montantTotal)')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.dateCommande > :threeMonthsAgo')
            ->setParameter('customer', $customer)
            ->setParameter('threeMonthsAgo', $threeMonthsAgo)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        if ($firstHalf == 0) {
            return 'new_customer';
        }

        $percentageChange = (($secondHalf - $firstHalf) / $firstHalf) * 100;

        if ($percentageChange > 20) {
            return 'increasing';
        } elseif ($percentageChange < -20) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Get all high-value customers (above a spending threshold).
     * 
     * @param float $minimumSpend Minimum spending threshold
     * @param int $limit Maximum number of customers
     * @return array<string, mixed> Array of high-value customers
     */
    public function getHighValueCustomers(float $minimumSpend = 1000, int $limit = 20): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u.id, u.email, SUM(c.montantTotal) as totalSpent, COUNT(c.id) as orderCount')
            ->from('App\Entity\Utilisateur', 'u')
            ->leftJoin('u.commandes', 'c')
            ->groupBy('u.id, u.email')
            ->having('SUM(c.montantTotal) >= :minimumSpend')
            ->setParameter('minimumSpend', $minimumSpend)
            ->orderBy('totalSpent', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get customers at risk of churn (haven't ordered recently).
     * 
     * @param int $daysInactive Number of days without order to consider at risk
     * @param int $limit Maximum number of customers
     * @return array<string, mixed> Array of at-risk customers
     */
    public function getAtRiskCustomers(int $daysInactive = 90, int $limit = 20): array
    {
        $cutoffDate = (new \DateTime())->modify("-{$daysInactive} days");

        return $this->entityManager->createQueryBuilder()
            ->select('u.id, u.email, MAX(c.dateCommande) as lastOrder, COUNT(c.id) as orderCount')
            ->from('App\Entity\Utilisateur', 'u')
            ->leftJoin('u.commandes', 'c')
            ->where('c.dateCommande <= :cutoffDate')
            ->orWhere('c.id IS NULL')
            ->groupBy('u.id, u.email')
            ->having('COUNT(c.id) > 0')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('lastOrder', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get one-time buyers (customers with only one order).
     * 
     * @param int $limit Maximum number of customers
     * @return array<string, mixed> Array of one-time buyers
     */
    public function getOneTimeBuyers(int $limit = 20): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u.id, u.email, c.dateCommande as lastOrder, c.montantTotal as orderValue')
            ->from('App\Entity\Utilisateur', 'u')
            ->leftJoin('u.commandes', 'c')
            ->groupBy('u.id, u.email')
            ->having('COUNT(c.id) = 1')
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average metrics across all customers.
     * 
     * @return array<string, mixed> Platform-wide average metrics
     */
    public function getPlatformAverageMetrics(): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('
                AVG(c.montantTotal) as avgOrderValue,
                COUNT(DISTINCT c.utilisateur) as totalCustomers,
                COUNT(DISTINCT c.id) as totalOrders,
                SUM(c.montantTotal) as totalRevenue
            ')
            ->from('App\Entity\CommandeProduit', 'c')
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'avgOrderValue' => $result['avgOrderValue'] ?? 0,
            'totalCustomers' => (int) ($result['totalCustomers'] ?? 0),
            'totalOrders' => (int) ($result['totalOrders'] ?? 0),
            'totalRevenue' => $result['totalRevenue'] ?? 0,
        ];
    }
}
