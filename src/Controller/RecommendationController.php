<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use App\Service\OrderAnalyticsService;
use App\Service\ProductRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for product recommendations and customer analytics.
 * 
 * Demonstrates usage of ProductRecommendationService and OrderAnalyticsService
 * with Doctrine Query Builder for efficient database queries.
 */
#[Route('/api/recommendations')]
class RecommendationController extends AbstractController
{
    public function __construct(
        private ProductRecommendationService $recommendationService,
        private OrderAnalyticsService $analyticsService,
        private ProduitRepository $produitRepository,
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    /**
     * Get products bought together with a specific product.
     * Example: /api/recommendations/co-purchases/5
     */
    #[Route('/co-purchases/{id}', name: 'app_products_bought_together', methods: ['GET'])]
    public function getProductsBoughtTogether(int $id): JsonResponse
    {
        $product = $this->produitRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $recommendations = $this->recommendationService->getProductsBoughtTogether($product, 5);

        return new JsonResponse([
            'product' => [
                'id' => $product->getId(),
                'nom' => $product->getNom(),
            ],
            'recommendations' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
                'image' => $p->getImage(),
            ], $recommendations),
            'message' => 'Customers who bought this also bought...',
        ]);
    }

    /**
     * Get personalized recommendations for a customer.
     * Example: /api/recommendations/for-customer/3
     */
    #[Route('/for-customer/{id}', name: 'app_customer_recommendations', methods: ['GET'])]
    public function getCustomerRecommendations(int $id): JsonResponse
    {
        $customer = $this->utilisateurRepository->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $recommendations = $this->recommendationService->getRecommendationsForCustomer($customer, 5);

        return new JsonResponse([
            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail() ?? 'N/A',
            ],
            'recommendations' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
                'image' => $p->getImage(),
            ], $recommendations),
            'message' => 'Personalized recommendations based on your purchase history',
        ]);
    }

    /**
     * Get related products by category.
     * Example: /api/recommendations/by-category/5
     */
    #[Route('/by-category/{id}', name: 'app_related_products', methods: ['GET'])]
    public function getRelatedProductsByCategory(int $id): JsonResponse
    {
        $product = $this->produitRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $related = $this->recommendationService->getRelatedProductsByCategory($product, 5);

        return new JsonResponse([
            'product' => ['id' => $product->getId(), 'nom' => $product->getNom()],
            'category' => [
                'id' => $product->getCategorie()->getId(),
                'nom' => $product->getCategorie()->getNom(),
            ],
            'related_products' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
            ], $related),
        ]);
    }

    /**
     * Get top-selling products.
     * Example: /api/recommendations/top-sellers
     */
    #[Route('/top-sellers', name: 'app_top_sellers', methods: ['GET'])]
    public function getTopSellers(): JsonResponse
    {
        $topProducts = $this->recommendationService->getTopSellingProducts(10);

        return new JsonResponse([
            'totalProducts' => count($topProducts),
            'products' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
                'image' => $p->getImage(),
            ], $topProducts),
            'message' => 'Top 10 best-selling products',
        ]);
    }

    /**
     * Get trending products (recent sales).
     * Example: /api/recommendations/trending?days=7
     */
    #[Route('/trending', name: 'app_trending_products', methods: ['GET'])]
    public function getTrendingProducts(): JsonResponse
    {
        $daysBack = $this->getRequest()->query->getInt('days', 7);
        $trending = $this->recommendationService->getTrendingProducts(5, $daysBack);

        return new JsonResponse([
            'period' => "Last {$daysBack} days",
            'products' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
            ], $trending),
        ]);
    }

    /**
     * Get high-rated products not yet purchased.
     * Example: /api/recommendations/high-rated/3
     */
    #[Route('/high-rated/{id}', name: 'app_high_rated_products', methods: ['GET'])]
    public function getHighRatedProducts(int $id): JsonResponse
    {
        $customer = $this->utilisateurRepository->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $highRated = $this->recommendationService->getHighRatedProducts($customer, 5, 4.0);

        return new JsonResponse([
            'customer' => ['id' => $customer->getId()],
            'minRating' => 4.0,
            'products' => array_map(fn($p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
            ], $highRated),
        ]);
    }
}
