Steps to add the product images and seed sample products

1. Copy your product images from your Downloads folder into the project:

   - Copy the RhinAction image and name it `rhinaction.jpg`
   - Copy the Zarbeil sirop image and name it `zarbeil.jpg`

   Put them into: `public/assets/img/products/`

   Example (PowerShell):

```powershell
copy "C:\Users\%USERNAME%\Downloads\rhinaction.jpg" "public/assets/img/products/rhinaction.jpg"
copy "C:\Users\%USERNAME%\Downloads\zarbeil.jpg" "public/assets/img/products/zarbeil.jpg"
```

2. Run the seed command to insert products into the database:

```bash
php bin/console app:seed-products
```

3. Open the catalogue: http://localhost:8000/catalogue/

Notes:
- The command will create a category `Médicaments` if it doesn't exist and add two products that reference the images above.
- If you prefer different filenames, update them in the command (`src/Command/SeedProductsCommand.php`) or rename your images to match.
