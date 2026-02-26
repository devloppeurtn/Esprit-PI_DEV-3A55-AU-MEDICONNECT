# Product Recommendations & Customer Analytics Implementation

This guide demonstrates how to implement **Product Recommendations** and **Order History & Customer Analytics** using Doctrine Query Builder in Symfony.

## Overview

### 5. Product Recommendations
Analyze purchase history to suggest related products. Implements "Customers who bought this also bought..." functionality similar to Amazon, increasing cross-selling opportunities.

### 6. Order History & Customer Analytics
Track comprehensive customer behavior patterns including:
- Total amount spent over time
- Order frequency
- Preferred product categories
- Average order value
- Repeat customer identification
- Customer segmentation
- Spending trends

## Architecture

The implementation uses **3 key components**:

### 1. ProductRecommendationService
Location: `src/Service/ProductRecommendationService.php`

**Core Methods:**
- `getProductsBoughtTogether(Produit, int)` - Find products frequently bought with a specific product
- `getRecommendationsForCustomer(Utilisateur, int)` - Personalized recommendations based on purchase history
- `getRelatedProductsByCategory(Produit, int)` - Similar products in same category
- `getTopSellingProducts(int)` - Best-selling products across all customers
- `getTrendingProducts(int, int)` - Recent high-sales-volume products
- `getHighRatedProducts(Utilisateur, float, int)` - Highly-rated products not yet purchased

### 2. OrderAnalyticsService
Location: `src/Service/OrderAnalyticsService.php`

**Core Methods:**
- `getCustomerAnalytics(Utilisateur)` - Comprehensive customer profile
- `getTotalSpent(Utilisateur)` - Lifetime value
- `getOrderCount(Utilisateur)` - Number of orders
- `getAverageOrderValue(Utilisateur)` - AOV calculation
- `getOrderFrequency(Utilisateur)` - Average days between orders
- `getPreferredCategories(Utilisateur, int)` - Category preferences
- `getOrdersOverTime(Utilisateur, int)` - Monthly breakdown
- `getCustomerSegment(Utilisateur)` - Gold/Silver/Bronze/Regular classification
- `getSpendingTrend(Utilisateur)` - Increasing/Stable/Decreasing
- `getHighValueCustomers(float, int)` - VIP customer list
- `getAtRiskCustomers(int, int)` - Churn prevention targets
- `getOneTimeBuyers(int)` - Repeat purchase targets
- `getPlatformAverageMetrics()` - Platform-wide KPIs

### 3. Enhanced Repositories
Added Query Builder methods to support efficient queries:
- `CommandeProduitRepository::findByDateRange()` - Date range queries
- `ProduitRepository::findCoProductsForProduct()` - Co-purchase analysis
- `ProduitRepository::findTopSellers()` - Sales ranking
- `LigneCommandeRepository::findCoPurchaseAssociations()` - Product associations

## Database Schema

The implementation uses existing entities:

```
CommandeProduit (Orders)
├── id
├── utilisateur_id (FK → Utilisateur)
├── dateCommande
├── montantTotal
├── statut (EN_ATTENTE, CONFIRMEE, LIVREE, ANNULEE)
└── lignesCommande (OneToMany)

LigneCommande (Order Items)
├── id
├── commande_id (FK → CommandeProduit)
├── produit_id (FK → Produit)
├── quantite
└── prixUnitaire

Produit (Products)
├── id
├── nom
├── description
├── prix
├── stock
├── categorie_id (FK → CategorieProduit)
└── lignesCommande (OneToMany)

CategorieProduit (Categories)
├── id
├── nom
└── produits (OneToMany)
```

## Usage Examples

### Service Injection

```php
use App\Service\ProductRecommendationService;
use App\Service\OrderAnalyticsService;

public function myController(
    ProductRecommendationService $recommendationService,
    OrderAnalyticsService $analyticsService,
)
{
    // Use the services
}
```

### Example 1: Get Products Bought Together

```php
$product = $this->produitRepository->find(5);
$recommendations = $this->recommendationService->getProductsBoughtTogether($product, 5);

foreach ($recommendations as $product) {
    echo $product->getNom(); // "Recommended Product"
}
```

**Query Generated:**
```sql
SELECT p.id, p.nom, p.prix, p.image, COUNT(lc2.id) as frequency
FROM ligne_commande lc1
INNER JOIN commande_produit c1 ON c1.id = lc1.commande_id
INNER JOIN ligne_commande lc2 ON c1.id = lc2.commande_id
INNER JOIN produit p ON p.id = lc2.produit_id
WHERE lc1.produit_id = 5 AND p.id != 5
GROUP BY p.id
ORDER BY frequency DESC
LIMIT 5
```

### Example 2: Customer Analytics

```php
$customer = $this->utilisateurRepository->find(3);
$analytics = $this->analyticsService->getCustomerAnalytics($customer);

// Returns:
// [
//     'totalSpent' => '2500.50',
//     'orderCount' => 12,
//     'averageOrderValue' => '208.37',
//     'orderFrequency' => 15.5, // days between orders
//     'isRepeatCustomer' => true,
//     'customerSegment' => 'Silver',
//     'spendingTrend' => 'increasing',
//     'preferredCategories' => [...],
//     'mostPurchasedProducts' => [...],
//     ...
// ]
```

### Example 3: High-Value Customers

```php
$topCustomers = $this->analyticsService->getHighValueCustomers(
    minimumSpend: 5000,
    limit: 20
);

foreach ($topCustomers as $customer) {
    echo $customer['email'];
    echo $customer['totalSpent']; // "€5250.00"
}
```

### Example 4: At-Risk Customers (Churn Prevention)

```php
// Find customers inactive for 90+ days
$atRisk = $this->analyticsService->getAtRiskCustomers(
    daysInactive: 90,
    limit: 20
);

foreach ($atRisk as $customer) {
    // Send re-engagement email
    // Offer special discount
}
```

### Example 5: Customer Segmentation

```php
$customer = $this->utilisateurRepository->find(5);
$segment = $this->analyticsService->getCustomerSegment($customer);

// Returns: "Gold", "Silver", "Bronze", or "Regular"
// Based on lifetime spending:
// - Gold: >= €5000
// - Silver: >= €2000
// - Bronze: >= €500
// - Regular: < €500
```

### Example 6: Order Timeline

```php
$timeline = $this->analyticsService->getOrdersOverTime($customer, 12);

// Returns monthly breakdown:
// [
//     ['month' => '2025-01', 'orderCount' => 2, 'monthlyRevenue' => '450.00'],
//     ['month' => '2025-02', 'orderCount' => 3, 'monthlyRevenue' => '620.50'],
//     ...
// ]
```

### Example 7: Preferred Categories

```php
$categories = $this->analyticsService->getPreferredCategories($customer, 5);

foreach ($categories as $category) {
    echo $category['categoryName'];        // "Electronics"
    echo $category['purchaseCount'];       // 5
    echo $category['totalQuantity'];       // 12
}
```

## API Endpoints

The `RecommendationAnalyticsController.php` provides REST endpoints:

### Recommendation Endpoints

```
GET  /api/recommendations/co-purchases/{id}
     Get products bought together with product #id

GET  /api/recommendations/for-customer/{id}
     Get personalized recommendations for customer #id

GET  /api/recommendations/by-category/{id}
     Get products in same category as product #id

GET  /api/recommendations/top-sellers
     Get top 10 best-selling products

GET  /api/recommendations/trending?days=7
     Get trending products (last N days)

GET  /api/recommendations/high-rated/{id}
     Get highly-rated products for customer #id
```

### Analytics Endpoints

```
GET  /api/analytics/customer/{id}
     Get comprehensive analytics for customer #id

GET  /api/analytics/customer/{id}/timeline?months=12
     Get monthly order breakdown for customer #id

GET  /api/analytics/high-value-customers?spend=1000&limit=20
     Get VIP customers (spending >= amount)

GET  /api/analytics/at-risk-customers?days=90&limit=20
     Get inactive customers (no orders in N days)

GET  /api/analytics/one-time-buyers?limit=20
     Get customers with only 1 order

GET  /api/analytics/platform-metrics
     Get platform-wide KPIs
```

## Query Builder Techniques Used

### 1. Subqueries in WHERE Clause

```php
->where('p.id NOT IN (
    SELECT DISTINCT lc.produit
    FROM App\Entity\LigneCommande lc
    WHERE lc.commande IN (...)
)')
```

### 2. Multiple JOINs with Conditions

```php
->innerJoin('App\Entity\LigneCommande', 'lc2', 'WITH', 'c1.id = lc2.commande')
->innerJoin('lc2.produit', 'p', 'WITH', 'p.id = lc2.produit')
```

### 3. Aggregation & GROUP BY

```php
->select('p.id, p.nom, SUM(lc.quantite) as totalSold')
->groupBy('p.id')
->orderBy('totalSold', 'DESC')
```

### 4. HAVING Clause

```php
->groupBy('p.id')
->having('AVG(a.note) >= :minRating')
->setParameter('minRating', 4.0)
```

### 5. Date Functions

```php
->where('c.dateCommande BETWEEN :from AND :to')
->setParameter('from', $startDate)
->setParameter('to', $endDate)
```

## Performance Optimization

### Indexing Strategy

Create database indexes for common queries:

```sql
-- Order queries
CREATE INDEX idx_commande_utilisateur ON commande_produit(utilisateur_id);
CREATE INDEX idx_commande_date ON commande_produit(date_commande);
CREATE INDEX idx_commande_statut ON commande_produit(statut);

-- Line item queries
CREATE INDEX idx_ligne_commande_produit ON ligne_commande(produit_id);
CREATE INDEX idx_ligne_commande_commande ON ligne_commande(commande_id);

-- Product queries
CREATE INDEX idx_produit_categorie ON produit(categorie_id);
CREATE INDEX idx_produit_stock ON produit(stock);
```

### Query Caching

For frequently accessed analytics:

```php
$analytics = $cache->get('customer_analytics_' . $customer->getId(), function() use ($customer) {
    return $this->analyticsService->getCustomerAnalytics($customer);
});
```

### Fetch Only Needed Fields

Use `SELECT` to fetch specific fields instead of full entities:

```php
->select('u.id, u.email, SUM(c.montantTotal) as totalSpent')
->from('App\Entity\Utilisateur', 'u')
```

## Integration Examples

### Twig Template for Product Page

```twig
{% if product %}
    <h2>{{ product.nom }}</h2>
    
    <!-- Recommendations Section -->
    <h3>Customers Also Bought</h3>
    {% for related in productsBoughtTogether %}
        <div class="product-card">
            <img src="{{ related.image }}" alt="{{ related.nom }}">
            <h4>{{ related.nom }}</h4>
            <p>€{{ related.prix }}</p>
        </div>
    {% endfor %}
{% endif %}
```

### Customer Dashboard

```twig
{% if customer %}
    <h2>Customer Profile</h2>
    
    <div class="analytics-cards">
        <div class="card">
            <h4>Total Spent</h4>
            <p>€{{ analytics.totalSpent }}</p>
        </div>
        <div class="card">
            <h4>Orders</h4>
            <p>{{ analytics.orderCount }}</p>
        </div>
        <div class="card">
            <h4>Average Order Value</h4>
            <p>€{{ analytics.averageOrderValue }}</p>
        </div>
        <div class="card">
            <h4>Segment</h4>
            <p class="badge badge-{{ analytics.customerSegment|lower }}">
                {{ analytics.customerSegment }}
            </p>
        </div>
    </div>
{% endif %}
```

## Testing

### Unit Test Example

```php
use App\Service\ProductRecommendationService;
use PHPUnit\Framework\TestCase;

class ProductRecommendationServiceTest extends TestCase
{
    private ProductRecommendationService $service;

    public function testGetProductsBoughtTogether(): void
    {
        $product = new Produit(); // Mock product
        $recommendations = $this->service->getProductsBoughtTogether($product, 5);

        $this->assertIsArray($recommendations);
        $this->assertLessThanOrEqual(5, count($recommendations));
    }

    public function testGetTopSellingProducts(): void
    {
        $topSellers = $this->service->getTopSellingProducts(10);

        $this->assertIsArray($topSellers);
        $this->assertCount(10, $topSellers);
    }
}
```

## Common Use Cases

### Cross-Sell Strategy
```php
// On checkout page, show products bought together
$recommendations = $this->recommendationService->getProductsBoughtTogether($product);
```

### Email Marketing
```php
// Send personalized recommendations
$customers = $this->analyticsService->getAtRiskCustomers(30);
foreach ($customers as $customer) {
    $recommendations = $this->recommendationService->getRecommendationsForCustomer($customer, 5);
    // Send email with recommendations
}
```

### Customer Analytics Dashboard
```php
// Admin dashboard showing customer segments
$goldCustomers = $this->analyticsService->getHighValueCustomers(5000);
$atRiskCustomers = $this->analyticsService->getAtRiskCustomers(60);
$oneTimeBuyers = $this->analyticsService->getOneTimeBuyers(20);
```

### Inventory Planning
```php
// Identify top-selling product combinations
$coProducts = $this->analyticsService->findCoPurchaseAssociations(20);
// Stock these products together for better inventory management
```

## Troubleshooting

### No Results
- Ensure products have been purchased (orders exist)
- Check order statuses - only CONFIRMEE and LIVREE are counted for analytics
- Verify customer has orders before requesting analytics

### Slow Queries
- Check database indexes are in place
- Use Query Profiler in Symfony Debug Toolbar
- Consider caching for frequently accessed reports

## Future Enhancements

1. **ML-based Recommendations** - Use collaborative filtering for smarter suggestions
2. **Real-time Analytics** - WebSocket updates for live dashboards
3. **Custom Segments** - Allow admin to create custom customer segments
4. **Predictive Analytics** - Forecast customer lifetime value
5. **A/B Testing** - Test different recommendation algorithms
6. **Export Reports** - CSV/PDF downloads for analytics

## Files Modified/Created

- ✅ `src/Service/ProductRecommendationService.php` - Product recommendation logic
- ✅ `src/Service/OrderAnalyticsService.php` - Customer analytics logic
- ✅ `src/Repository/CommandeProduitRepository.php` - Added query builder methods
- ✅ `src/Repository/ProduitRepository.php` - Added query builder methods
- ✅ `src/Repository/LigneCommandeRepository.php` - Added query builder methods
- ✅ `src/Controller/RecommendationAnalyticsController.php` - API endpoints

## Key Benefits

✅ Increases cross-selling opportunities
✅ Improves customer experience with personalized recommendations
✅ Identifies valuable customer segments
✅ Enables proactive churn prevention
✅ Supports data-driven marketing strategies
✅ Provides actionable business intelligence
✅ Implements best practices from e-commerce leaders
