# Test Data for Module 3

To test Module 3 functionality, insert these sample products and categories into your database.

## SQL Insert Statements

```sql
-- Insert Categories
INSERT INTO categorie_produit (nom, description, image) VALUES
('Électronique', 'Appareils électroniques et électroménagers', NULL),
('Mode & Accessoires', 'Vêtements, chaussures et accessoires', NULL),
('Livres', 'Livres et matériaux éducatifs', NULL),
('Maison & Jardin', 'Articles pour la maison et le jardin', NULL),
('Sports & Loisirs', 'Équipements et accessoires de sport', NULL);

-- Insert Products
INSERT INTO produit (nom, description, prix, stock, categorie_id, image) VALUES
-- Electronics
('Laptop Dell XPS 13', 'Ordinateur portable 13 pouces haute performance', '1299.99', 5, 1, NULL),
('iPhone 15 Pro', 'Smartphone dernière génération', '999.99', 10, 1, NULL),
('Casque Sony WH-1000XM5', 'Casque audio avec réduction de bruit', '349.99', 15, 1, NULL),
('Clavier Mécanique RGB', 'Clavier gaming avec switches mécanique', '129.99', 20, 1, NULL),
('Souris Logitech MX Master', 'Souris sans fil professionnelle', '99.99', 25, 1, NULL),

-- Fashion
('Chemise Coton Premium', 'Chemise formelle en coton 100% naturel', '49.99', 30, 2, NULL),
('Jeans Slim Fit', 'Jean bleu délavé coupe cintrée', '69.99', 40, 2, NULL),
('Montre Montre Élégante', 'Montre analogique acier inoxydable', '199.99', 8, 2, NULL),
('Sac à Dos Voyage', 'Grand sac à dos avec nombreux compartiments', '89.99', 12, 2, NULL),
('Sneakers Air Zoom', 'Chaussures de sport confortables', '129.99', 18, 2, NULL),

-- Books
('Clean Code', 'Guide pour écrire un meilleur code', '29.99', 50, 3, NULL),
('Design Patterns', 'Solutions réutilisables aux problèmes courants', '39.99', 35, 3, NULL),
('Algorithmique Avancée', 'Concepts et applications practiques', '49.99', 20, 3, NULL),
('Web Moderne avec JavaScript', 'Développement web moderne complet', '44.99', 25, 3, NULL),

-- Home & Garden
('Lampe LED Smartlight', 'Ampoule LED commandable par téléphone', '24.99', 60, 4, NULL),
('Plante Verte Artificielle', 'Plante décorative haut de gamme', '34.99', 15, 4, NULL),
('Coussin Premium', 'Coussin de décoration 50x50cm', '19.99', 40, 4, NULL),

-- Sports
('Tapis de Yoga', 'Tapis de yoga antidérapant premium', '39.99', 20, 5, NULL),
('Haltères Ajustables', 'Set d\'haltères de 2kg à 10kg', '129.99', 10, 5, NULL),
('Ballon de Football', 'Ballon réglementaire officiel', '29.99', 30, 5, NULL);
```

## How to Insert

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Select database `mediconnect`
3. Go to SQL tab
4. Paste the above SQL
5. Click Execute

### Option 2: Using MySQL CLI
```bash
mysql -u root mediconnect < insert_test_data.sql
```

### Option 3: Using Symfony Command
```bash
php bin/console doctrine:query:sql "INSERT INTO categorie_produit..."
```

## Test User

The application uses the existing user authentication:
- Email: Test with any user created via signup
- Orders will be linked to logged-in user

## What to Test After Inserting Data

1. **Catalogue Page**: `/catalogue/` - Should show all products
2. **Search**: Test searching for "laptop", "code", "shirt"
3. **Categories**: Filter by each category
4. **Add to Cart**: Add various products
5. **Cart**: View and modify quantities
6. **Checkout**: Complete purchase flow
7. **Order History**: View created orders

## Sample Test Flow

1. Login or create account
2. Go to `/catalogue/`
3. Search for "code" → should find books with algorithms
4. Click "Design Patterns" → view detail
5. Add 2 copies to cart
6. Go back, add "Laptop" 
7. Go to cart → verify both items
8. Go to checkout → enter address
9. Submit → order created
10. Go to "Mes commandes" → see order with "En Attente" status
