<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Repository\CategorieProduitRepository;
use App\Repository\ProduitRepository;
use App\Service\StockDemandForecastService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products')]
class ProductAdminController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(
        ProduitRepository $repo,
        StockDemandForecastService $stockDemandForecastService
    ): Response
    {
        $products = $repo->findAll();
        $forecastByProduct = $stockDemandForecastService->forecastForProducts($products);
        $forecastAlerts = array_filter(
            $forecastByProduct,
            static fn (array $item): bool => $item['should_alert'] === true
        );

        uasort(
            $forecastAlerts,
            static function (array $a, array $b): int {
                $aDays = $a['days_until_stockout'] ?? PHP_INT_MAX;
                $bDays = $b['days_until_stockout'] ?? PHP_INT_MAX;

                return $aDays <=> $bDays;
            }
        );

        return $this->render('admin/products/index.html.twig', [
            'products' => $products,
            'forecastByProduct' => $forecastByProduct,
            'forecastAlerts' => array_slice($forecastAlerts, 0, 8, true),
        ]);
    }

    #[Route('/new', name: 'admin_products_new')]
    #[Route('/{id}/edit', name: 'admin_products_edit', requirements: ['id' => '\d+'])]
    public function form(
        Request $request,
        EntityManagerInterface $em,
        CategorieProduitRepository $catRepo,
        ?Produit $produit = null
    ): Response {
        $produit = $produit ?? new Produit();
        $produit->setStock($produit->getStock() ?? 0);
        $produit->setPrix($produit->getPrix() ?? '0.00');
        $routeName = $produit->getId() ? 'admin_products_edit' : 'admin_products_new';
        $routeParams = $produit->getId() ? ['id' => $produit->getId()] : [];

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom', ''));
            $prixRaw = trim((string) $request->request->get('prix', ''));
            $stockRaw = trim((string) $request->request->get('stock', ''));
            $catId = (int) $request->request->get('categorie', 0);
            $imageSource = (string) $request->request->get('image_source', 'url');
            $imageUrl = trim((string) $request->request->get('image_url', ''));
            $imageFile = $request->files->get('image_file');

            if ($nom === '') {
                $this->addFlash('error', 'Le nom du produit est obligatoire.');
                return $this->redirectToRoute($routeName, $routeParams);
            }

            if ($prixRaw === '') {
                $this->addFlash('error', 'Le prix est obligatoire.');
                return $this->redirectToRoute($routeName, $routeParams);
            }
            if (!is_numeric($prixRaw) || (float) $prixRaw < 0) {
                $this->addFlash('error', 'Le prix est invalide.');
                return $this->redirectToRoute($routeName, $routeParams);
            }

            if ($stockRaw === '') {
                $this->addFlash('error', 'Le stock est obligatoire.');
                return $this->redirectToRoute($routeName, $routeParams);
            }
            if (!ctype_digit($stockRaw)) {
                $this->addFlash('error', 'Le stock doit être un nombre entier positif.');
                return $this->redirectToRoute($routeName, $routeParams);
            }

            $categorie = $catRepo->find($catId);
            if (!$categorie) {
                $this->addFlash('error', 'La catégorie est obligatoire.');
                return $this->redirectToRoute($routeName, $routeParams);
            }

            $produit->setNom($nom);
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((string) $prixRaw);
            $produit->setStock((int) $stockRaw);
            $produit->setCategorie($categorie);

            $oldImage = $produit->getImage();

            if ($imageSource === 'upload') {
                if ($imageFile instanceof UploadedFile) {
                    $uploadedPath = $this->uploadImage($imageFile, 'products');

                    if ($uploadedPath === null) {
                        $this->addFlash('error', 'Échec de l\'upload image.');
                        return $this->redirectToRoute($routeName, $routeParams);
                    }

                    $produit->setImage($uploadedPath);
                    if ($oldImage !== $uploadedPath) {
                        $this->deleteLocalImage($oldImage);
                    }
                }
            } else {
                if ($imageUrl !== '') {
                    $produit->setImage($imageUrl);
                    if ($oldImage !== $imageUrl) {
                        $this->deleteLocalImage($oldImage);
                    }
                }
            }

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/form.html.twig', [
            'product' => $produit,
            'categories' => $catRepo->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_products_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Produit $produit, EntityManagerInterface $em): Response
    {
        foreach ($produit->getLignesCommande()->toArray() as $ligne) {
            $em->remove($ligne);
        }

        foreach ($produit->getAvisProduits()->toArray() as $avis) {
            $em->remove($avis);
        }

        $imagePath = $produit->getImage();

        $em->remove($produit);
        $em->flush();

        $this->deleteLocalImage($imagePath);

        $this->addFlash('success', 'Produit supprime.');
        return $this->redirectToRoute('admin_products');
    }

    private function uploadImage(UploadedFile $file, string $folder): ?string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $originalName) ?: 'image';
        $safeName = trim($safeName, '-');
        if ($safeName === '') {
            $safeName = 'image';
        }

        $extension = $file->guessExtension() ?: ($file->getClientOriginalExtension() ?: 'bin');
        $fileName = sprintf('%s-%s.%s', $safeName, uniqid('', true), $extension);
        $relativeDir = 'uploads/' . $folder;
        $targetDir = $this->getParameter('kernel.project_dir') . '/public/' . $relativeDir;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return null;
        }

        try {
            $file->move($targetDir, $fileName);
        } catch (FileException) {
            return null;
        }

        return $relativeDir . '/' . $fileName;
    }

    private function deleteLocalImage(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'uploads/')) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
