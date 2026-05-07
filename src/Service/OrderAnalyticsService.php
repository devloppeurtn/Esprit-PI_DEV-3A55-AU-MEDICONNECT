<?php

namespace App\Service;

use App\DTO\CategoryPurchaseSummary;
use App\DTO\CustomerAtRiskSummary;
use App\DTO\CustomerValueSummary;
use App\DTO\OneTimeBuyerSummary;
use App\DTO\ProductPurchaseSummary;
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
                StatutCommande::VALIDEE,
                StatutCommande::PREPAREE,
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
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id) as orderCount, MIN(c.dateCommande) as firstOrder, MAX(c.dateCommande) as lastOrder')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();

        $count = (int) ($result['orderCount'] ?? 0);
        if ($count < 2) {
            return -1;
        }

        $firstOrder = $result['firstOrder'] ?? null;
        $lastOrder = $result['lastOrder'] ?? null;
        if (!$firstOrder instanceof \DateTimeInterface || !$lastOrder instanceof \DateTimeInterface) {
            return -1;
        }
        
        $daysSpan = $firstOrder->diff($lastOrder)->days;
        $frequency = $daysSpan / ($count - 1);

        return round($frequency, 2);
    }

    /**
     * Get preferred product categories for a customer.
     * 
     * @param Utilisateur $customer The customer
     * @param int $limit Number of top categories to return
     * @return array<int, array<string, mixed>> Array of categories with purchase count
     */
    public function getPreferredCategories(Utilisateur $customer, int $limit = 5): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('NEW App\\DTO\\CategoryPurchaseSummary(cat.id, cat.nom, COUNT(lc.id), SUM(lc.quantite))')
            ->addSelect('COUNT(lc.id) AS HIDDEN purchaseCount')
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

        return array_map(
            static fn (CategoryPurchaseSummary $row): array => $row->toArray(),
            $rows
        );
    }

    public function getPreferredCategory(Utilisateur $customer): ?string
    {
        $categories = $this->getPreferredCategories($customer, 1);
        if ($categories === [] || !isset($categories[0]['categoryName'])) {
            return null;
        }

        return (string) $categories[0]['categoryName'];
    }

    /**
     * Get order history with monthly breakdown.
     * 
     * @param Utilisateur $customer The customer
     * @param int $months Number of months to look back
     * @return array<int, array<string, mixed>> Orders grouped by month
     */
    public function getOrdersOverTime(Utilisateur $customer, int $months = 12): array
    {
        $dateFrom = (new \DateTimeImmutable())->modify("-{$months} months");
        $conn = $this->entityManager->getConnection();
        $orderTable = $this->entityManager->getClassMetadata('App\Entity\CommandeProduit')->getTableName();

        $sql = <<<SQL
SELECT
    DATE_FORMAT(date_commande, '%Y-%m') AS month,
    COUNT(id) AS orderCount,
    SUM(montant_total) AS monthlyRevenue,
    AVG(montant_total) AS avgOrderValue
FROM {$orderTable}
WHERE utilisateur_id = :customerId
  AND date_commande >= :dateFrom
GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
ORDER BY month ASC
SQL;

        $results = $conn->executeQuery(
            $sql,
            [
                'customerId' => $customer->getId(),
                'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            ]
        )->fetchAllAssociative();

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
        $rows = $this->entityManager->createQueryBuilder()
            ->select('NEW App\\DTO\\ProductPurchaseSummary(p.id, p.nom, p.image, SUM(lc.quantite), COUNT(DISTINCT c.id))')
            ->addSelect('SUM(lc.quantite) AS HIDDEN totalQuantity')
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

        return array_map(
            static fn (ProductPurchaseSummary $row): array => $row->toArray(),
            $rows
        );
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

        $firstHalfRaw = $this->entityManager->createQueryBuilder()
            ->select('SUM(c.montantTotal)')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.dateCommande BETWEEN :sixMonthsAgo AND :threeMonthsAgo')
            ->setParameter('customer', $customer)
            ->setParameter('sixMonthsAgo', $sixMonthsAgo)
            ->setParameter('threeMonthsAgo', $threeMonthsAgo)
            ->getQuery()
            ->getSingleScalarResult();
        $firstHalf = is_numeric($firstHalfRaw) ? (float) $firstHalfRaw : 0.0;

        $secondHalfRaw = $this->entityManager->createQueryBuilder()
            ->select('SUM(c.montantTotal)')
            ->from('App\Entity\CommandeProduit', 'c')
            ->where('c.utilisateur = :customer')
            ->andWhere('c.dateCommande > :threeMonthsAgo')
            ->setParameter('customer', $customer)
            ->setParameter('threeMonthsAgo', $threeMonthsAgo)
            ->getQuery()
            ->getSingleScalarResult();
        $secondHalf = is_numeric($secondHalfRaw) ? (float) $secondHalfRaw : 0.0;

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
        $rows = $this->entityManager->createQueryBuilder()
            ->select('NEW App\\DTO\\CustomerValueSummary(u.id, u.email, SUM(c.montantTotal), COUNT(c.id))')
            ->addSelect('SUM(c.montantTotal) AS HIDDEN totalSpent')
            ->from('App\Entity\Utilisateur', 'u')
            ->leftJoin('u.commandes', 'c')
            ->groupBy('u.id, u.email')
            ->having('SUM(c.montantTotal) >= :minimumSpend')
            ->setParameter('minimumSpend', $minimumSpend)
            ->orderBy('totalSpent', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (CustomerValueSummary $row): array => $row->toArray(),
            $rows
        );
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

        $rows = $this->entityManager->createQueryBuilder()
            ->select('NEW App\\DTO\\CustomerAtRiskSummary(u.id, u.email, MAX(c.dateCommande), COUNT(c.id))')
            ->addSelect('MAX(c.dateCommande) AS HIDDEN lastOrder')
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

        return array_map(
            static fn (CustomerAtRiskSummary $row): array => $row->toArray(),
            $rows
        );
    }

    /**
     * Get one-time buyers (customers with only one order).
     * 
     * @param int $limit Maximum number of customers
     * @return array<string, mixed> Array of one-time buyers
     */
    public function getOneTimeBuyers(int $limit = 20): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('NEW App\\DTO\\OneTimeBuyerSummary(u.id, u.email, MAX(c.dateCommande), MAX(c.montantTotal))')
            ->addSelect('MAX(c.dateCommande) AS HIDDEN lastOrder')
            ->from('App\Entity\Utilisateur', 'u')
            ->leftJoin('u.commandes', 'c')
            ->groupBy('u.id, u.email')
            ->having('COUNT(c.id) = 1')
            ->orderBy('lastOrder', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (OneTimeBuyerSummary $row): array => $row->toArray(),
            $rows
        );
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
