<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\OrderAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Analytics API Controller
 * 
 * Provides endpoints for viewing customer behavior and order analytics.
 */
#[Route('/api/analytics')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private OrderAnalyticsService $analyticsService,
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    /**
     * Get comprehensive customer analytics.
     * Example: /api/analytics/customer/3
     */
    #[Route('/customer/{id}', name: 'app_customer_analytics', methods: ['GET'])]
    public function getCustomerAnalytics(int $id): JsonResponse
    {
        $customer = $this->utilisateurRepository->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $analytics = $this->analyticsService->getCustomerAnalytics($customer);

        return new JsonResponse([
            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
            ],
            'analytics' => [
                'totalSpent' => $analytics['totalSpent'],
                'orderCount' => $analytics['orderCount'],
                'averageOrderValue' => $analytics['averageOrderValue'],
                'orderFrequency' => $analytics['orderFrequency'],
                'isRepeatCustomer' => $analytics['isRepeatCustomer'],
                'segment' => $analytics['customerSegment'],
                'spendingTrend' => $analytics['spendingTrend'],
                'lastOrder' => $analytics['lastOrderDate'],
                'firstOrder' => $analytics['firstOrderDate'],
                'lifespan' => $analytics['lifespan'] . ' days',
                'preferredCategories' => $analytics['preferredCategories'],
                'topProducts' => $analytics['mostPurchasedProducts'],
            ],
        ]);
    }

    /**
     * Get orders timeline for a customer.
     * Example: /api/analytics/customer/3/timeline?months=12
     */
    #[Route('/customer/{id}/timeline', name: 'app_customer_timeline', methods: ['GET'])]
    public function getCustomerTimeline(int $id, Request $request): JsonResponse
    {
        $customer = $this->utilisateurRepository->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $months = $request->query->getInt('months', 12);
        $timeline = $this->analyticsService->getOrdersOverTime($customer, $months);

        return new JsonResponse([
            'customer' => ['id' => $customer->getId(), 'email' => $customer->getEmail()],
            'period' => "Last {$months} months",
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get high-value customers.
     * Example: /api/analytics/high-value-customers?spend=1000&limit=20
     */
    #[Route('/high-value-customers', name: 'app_high_value_customers', methods: ['GET'])]
    public function getHighValueCustomers(Request $request): JsonResponse
    {
        $minimumSpend = $request->query->get('spend', 1000);
        $minimumSpend = is_numeric((string) $minimumSpend) ? (float) $minimumSpend : 1000.0;
        $limit = $request->query->getInt('limit', 20);

        $customers = $this->analyticsService->getHighValueCustomers($minimumSpend, $limit);

        return new JsonResponse([
            'minimumSpend' => $minimumSpend,
            'totalFound' => count($customers),
            'customers' => $customers,
        ]);
    }

    /**
     * Get at-risk customers (haven't ordered recently).
     * Example: /api/analytics/at-risk-customers?days=90&limit=20
     */
    #[Route('/at-risk-customers', name: 'app_at_risk_customers', methods: ['GET'])]
    public function getAtRiskCustomers(Request $request): JsonResponse
    {
        $daysInactive = $request->query->getInt('days', 90);
        $limit = $request->query->getInt('limit', 20);

        $customers = $this->analyticsService->getAtRiskCustomers($daysInactive, $limit);

        return new JsonResponse([
            'inactiveDays' => $daysInactive,
            'totalFound' => count($customers),
            'customers' => $customers,
        ]);
    }

    /**
     * Get one-time buyers.
     * Example: /api/analytics/one-time-buyers
     */
    #[Route('/one-time-buyers', name: 'app_one_time_buyers', methods: ['GET'])]
    public function getOneTimeBuyers(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 20);
        $buyers = $this->analyticsService->getOneTimeBuyers($limit);

        return new JsonResponse([
            'totalFound' => count($buyers),
            'buyers' => $buyers,
        ]);
    }

    /**
     * Get platform-wide average metrics.
     * Example: /api/analytics/platform-metrics
     */
    #[Route('/platform-metrics', name: 'app_platform_metrics', methods: ['GET'])]
    public function getPlatformMetrics(): JsonResponse
    {
        $metrics = $this->analyticsService->getPlatformAverageMetrics();

        return new JsonResponse([
            'metrics' => [
                'avgOrderValue' => $metrics['avgOrderValue'],
                'totalCustomers' => $metrics['totalCustomers'],
                'totalOrders' => $metrics['totalOrders'],
                'totalRevenue' => $metrics['totalRevenue'],
                'avgOrdersPerCustomer' => $metrics['totalOrders'] > 0 
                    ? round($metrics['totalOrders'] / $metrics['totalCustomers'], 2)
                    : 0,
            ],
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
