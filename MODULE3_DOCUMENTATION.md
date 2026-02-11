# Module 3 - E-Commerce Module Implementation

## Overview
Module 3 implements a complete user-facing e-commerce system for MediConnect, including:
- Product catalogue with search/filter
- Shopping cart (session-based)
- Checkout process
- Order history with statuses
- AJAX functionality for seamless UX

## Data Model

### Entities Created

#### 1. **CategorieProduit** (`src/Entity/CategorieProduit.php`)
- Properties: `id`, `nom`, `description`, `image`
- Relations: One-to-Many with Produit
- Methods: __toString()

#### 2. **Produit** (`src/Entity/Produit.php`)
- Properties: `id`, `nom`, `description`, `prix` (DECIMAL), `stock`, `image`
- Relations: 
  - Many-to-One with CategorieProduit
  - One-to-Many with LigneCommande
- Methods: __toString()

#### 3. **StatutCommande** Enum (`src/Enum/StatutCommande.php`)
- Values:
  - `EN_ATTENTE` (In Progress) - Yellow
  - `VALIDEE` (Validated) - Blue
  - `PREPAREE` (Prepared) - Primary
  - `LIVREE` (Delivered) - Green
  - `ANNULEE` (Cancelled) - Red
- Methods: 
  - `getLabel()` - Returns French label
  - `getColor()` - Returns Bootstrap color class

#### 4. **CommandeProduit** (`src/Entity/CommandeProduit.php`)
- Properties: `id`, `dateCommande`, `statut` (enum), `montantTotal`, `adresseLivraison`
- Relations:
  - Many-to-One with Utilisateur
  - One-to-Many with LigneCommande (cascade delete)
- Methods:
  - `calculerMontantTotal()` - Sum of all line items

#### 5. **LigneCommande** (`src/Entity/LigneCommande.php`)
- Properties: `id`, `quantite`, `prixUnitaire` (DECIMAL)
- Relations:
  - Many-to-One with CommandeProduit
  - Many-to-One with Produit
- Methods:
  - `getSousTotal()` - Quantity × Price

#### 6. **Utilisateur** (Updated)
- Added: `commandes` Collection (One-to-Many with CommandeProduit)

## Services

### CartService (`src/Service/CartService.php`)
Manages shopping cart stored in user session.

**Key Methods:**
- `addToCart(Produit, quantity)` - Add product or increase quantity
- `removeFromCart(productId)` - Remove product from cart
- `updateQuantity(productId, quantity)` - Update quantity (removes if 0)
- `getCart()` - Return all cart items
- `getCartCount()` - Total quantity of items
- `getCartTotal()` - Total price
- `clearCart()` - Empty the cart

## Controllers

### 1. ProductController (`src/Controller/ProductController.php`)

#### Routes:
| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/catalogue/` | GET | `app_catalogue` | Display all products |
| `/catalogue/categorie/{id}` | GET | `app_catalogue_categorie` | Filter by category |
| `/catalogue/search` | GET | `app_catalogue_search` | Search products (full page) |
| `/catalogue/search-ajax` | GET | `app_catalogue_search_ajax` | Search AJAX (returns partial) |
| `/catalogue/{id}` | GET | `app_product_detail` | Product detail page |

**Key Features:**
- Dynamic category filtering
- Search with minimum 2 characters
- AJAX search returns Twig partial

### 2. CartController (`src/Controller/CartController.php`)

#### Routes:
| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/panier/` | GET | `app_cart` | Display cart |
| `/panier/add/{id}` | POST | `app_cart_add` | AJAX: Add to cart (JSON) |
| `/panier/update/{id}` | POST | `app_cart_update` | AJAX: Update quantity (JSON) |
| `/panier/remove/{id}` | POST | `app_cart_remove` | AJAX: Remove item (JSON) |
| `/panier/clear` | POST | `app_cart_clear` | AJAX: Clear cart (JSON) |

**AJAX Responses:**
All AJAX endpoints return JSON with:
```json
{
  "success": true,
  "message": "Message",
  "cartCount": 5,
  "cartTotal": "299.99",
  "itemTotal": "99.99"  // for updates only
}
```

### 3. OrderController (`src/Controller/OrderController.php`)

#### Routes:
| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/commandes/` | GET | `app_orders_list` | User's order history |
| `/commandes/{id}` | GET | `app_order_detail` | Order details & status |
| `/commandes/checkout` | GET,POST | `app_checkout` | Checkout form & processing |

**Key Features:**
- Checkout creates `CommandeProduit` with `EN_ATTENTE` status
- Creates `LigneCommande` rows from cart items
- Saves purchase price per line item
- Clears cart after successful order
- Shows order status timeline
- Requires authentication (ROLE_USER)

## Templates

### Catalogue Section

1. **`templates/catalogue/index.html.twig`**
   - Main catalogue page
   - Categories sidebar
   - AJAX search input with debounce
   - Cart badge
   - Product list container (filled by partial)

2. **`templates/catalogue/_product_list.html.twig`** (Reusable Partial)
   - Bootstrap card grid (responsive)
   - Product image, name, description, category
   - Price and stock display
   - "Add to Cart" button with AJAX
   - "View Details" link
   - Success/error notifications

3. **`templates/catalogue/product_detail.html.twig`**
   - Product image
   - Breadcrumb navigation
   - Full description
   - Category badge
   - Stock status
   - Quantity selector (+/−)
   - Add to cart form with AJAX

### Cart Section

4. **`templates/cart/index.html.twig`**
   - Cart items list with:
     - Product image, name, price
     - Quantity controls (−/+/input)
     - Line item total
     - Remove button
   - Order summary (sticky sidebar)
   - Subtotal display
   - Checkout button
   - Clear cart confirmation

### Order Section

5. **`templates/order/checkout.html.twig`**
   - Order form with:
     - User info (disabled fields)
     - Delivery address (textarea)
   - Order items preview
   - Total calculation
   - Confirm button

6. **`templates/order/list.html.twig`**
   - User's orders grid (cards)
   - Each card shows:
     - Order #id
     - Status badge (colored)
     - Date
     - Number of items
     - Total amount
     - "View Details" button

7. **`templates/order/detail.html.twig`**
   - Order header with status
   - Status timeline visualization
   - Order items table with:
     - Product name & category
     - Quantity
     - Unit price
     - Line total
   - Customer info card
   - Delivery address card
   - Total summary (sticky)
   - Print button

## AJAX Implementation Details

### 1. Product Search (Debounced)
**File:** `templates/catalogue/index.html.twig`

```javascript
// Debounce with 300ms delay
fetch('/catalogue/search-ajax?q=...')
  .then(response => response.text())
  .then(html => {
    document.getElementById('productList').innerHTML = html;
  });
```

**Returns:** Twig partial (`_product_list.html.twig`) with filtered products

---

### 2. Add to Cart
**File:** `templates/catalogue/_product_list.html.twig` & `product_detail.html.twig`

```javascript
fetch('/panier/add/5', {
  method: 'POST',
  body: new FormData().append('quantity', 1)
})
.then(response => response.json())
.then(data => {
  // Update cart badge: data.cartCount
  // Show notification: data.message
  // Refresh cart total: data.cartTotal
});
```

**Updates:**
- Cart badge count (realtime)
- Success notification toast
- Cart total (if visible)

---

### 3. Cart Quantity Update
**File:** `templates/cart/index.html.twig`

```javascript
fetch('/panier/update/5', {
  method: 'POST',
  body: new FormData().append('quantity', 3)
})
.then(response => response.json())
.then(data => {
  // Update line total
  // Update page total
  // Update cart badge
});
```

**Updates:**
- Line item total (immediately)
- Cart total (immediately)
- Cart badge count

---

### 4. Remove from Cart
```javascript
fetch('/panier/remove/5', {
  method: 'POST'
})
.then(response => response.json())
.then(data => {
  // Remove item from DOM
  // Reload page if empty
});
```

---

### 5. Clear Cart
```javascript
fetch('/panier/clear', { method: 'POST' })
.then(() => location.reload());
```

## Database Schema

### Tables Created:
- `categorie_produit`
- `produit` (foreign key to categorie_produit)
- `commande_produit` (foreign key to utilisateur)
- `ligne_commande` (foreign keys to commande_produit & produit)

### Updated Tables:
- `utilisateur` (added commande_produit relationship)

## Testing Checklist

### Manual Testing:

1. **Product Catalogue**
   - [ ] Load `/catalogue/` - displays all products
   - [ ] Click category - filters products
   - [ ] Search by name - AJAX updates list
   - [ ] Click product - shows detail page
   - [ ] Stock display correct
   - [ ] Cart badge updates

2. **Add to Cart**
   - [ ] Add from catalogue - success message
   - [ ] Add from detail page - updates quantity if exists
   - [ ] Try adding out-of-stock product - disabled
   - [ ] Add multiple products - cart shows all
   - [ ] Badge shows correct count

3. **Shopping Cart**
   - [ ] View `/panier/` - shows all items
   - [ ] Increase quantity - line total updates
   - [ ] Decrease quantity - line total updates
   - [ ] Remove item - disappears from DOM
   - [ ] Remove last item - shows empty state
   - [ ] Clear cart - confirms and empties

4. **Checkout**
   - [ ] Load `/commandes/checkout` - requires auth
   - [ ] Form shows user info
   - [ ] Can enter delivery address
   - [ ] Click confirm - creates order
   - [ ] Order created with `EN_ATTENTE` status
   - [ ] Cart cleared after order
   - [ ] Redirects to order detail

5. **Order History**
   - [ ] Load `/commandes/` - shows user's orders
   - [ ] Click order - shows detail page
   - [ ] Order detail shows:
     - [ ] All line items
     - [ ] Correct purchase prices
     - [ ] Status timeline
     - [ ] Delivery address
   - [ ] Status timeline shows current status
   - [ ] Can print order

### Screenshots for Teacher:

Take screenshots of:

1. **Catalogue with search**
   - Search field with results updating live (AJAX)
   - Cart badge visible

2. **Add to cart (AJAX)**
   - Before: Product in catalogue
   - After: Success toast + badge updated (no page reload)

3. **Cart operations (AJAX)**
   - Updating quantity without reload
   - Totals recalculating live

4. **Checkout flow**
   - Filled form
   - Order confirmation page

5. **Order history**
   - List of orders with statuses
   - Order detail with timeline

6. **Browser Console**
   - Network tab showing AJAX requests (no page reloads for cart operations)
   - JSON responses from API

## Code Quality

- ✅ All templates validated
- ✅ All YAML configs valid
- ✅ All routes registered
- ✅ Proper Symfony conventions
- ✅ Bootstrap responsive design
- ✅ Session-based cart (no database)
- ✅ Order status enum for type safety
- ✅ Cascade delete for order items
- ✅ AJAX returns JSON for clean separation

## Files Created/Modified:

### New Files:
```
src/Entity/
  ├─ CategorieProduit.php
  ├─ Produit.php
  ├─ CommandeProduit.php
  ├─ LigneCommande.php

src/Enum/
  └─ StatutCommande.php

src/Repository/
  ├─ CategorieProduitRepository.php
  ├─ ProduitRepository.php
  ├─ CommandeProduitRepository.php
  └─ LigneCommandeRepository.php

src/Service/
  └─ CartService.php

src/Controller/
  ├─ ProductController.php
  ├─ CartController.php
  └─ OrderController.php

templates/
  ├─ catalogue/
  │  ├─ index.html.twig
  │  ├─ _product_list.html.twig
  │  └─ product_detail.html.twig
  ├─ cart/
  │  └─ index.html.twig
  └─ order/
     ├─ checkout.html.twig
     ├─ list.html.twig
     └─ detail.html.twig
```

### Modified Files:
```
src/Entity/Utilisateur.php
  └─ Added: commandes OneToMany relationship
```

## Deployment Notes

1. **Database**: Run migrations (already applied)
2. **Routes**: All auto-registered via attributes
3. **Services**: CartService auto-wired
4. **Templates**: Uses Twig 3 syntax
5. **Frontend**: Bootstrap 5 + Vanilla JS (no jQuery)

## Future Enhancements

- [ ] Payment gateway integration
- [ ] Email notifications for orders
- [ ] Admin order status updates
- [ ] Product reviews/ratings
- [ ] Wishlist functionality
- [ ] Coupon/discount codes
- [ ] Inventory management
- [ ] Order tracking
