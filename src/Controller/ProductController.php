<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Repository\CategorieProduitRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/catalogue')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_catalogue', methods: ['GET'])]
    public function index(
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService
    ): Response {
        $categories = $categorieRepo->findAll();
        $produits = $produitRepo->findAll();
        $cartCount = $cartService->getCartCount();

        return $this->render('catalogue/index.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/categorie/{id}', name: 'app_catalogue_categorie', methods: ['GET'])]
    public function byCategorie(
        int $id,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService
    ): Response {
        $categorie = $categorieRepo->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $categories = $categorieRepo->findAll();
        $produits = $produitRepo->findByCategorie($id);
        $cartCount = $cartService->getCartCount();

        return $this->render('catalogue/index.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'categorieActive' => $categorie,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/search', name: 'app_catalogue_search', methods: ['GET'])]
    public function search(
        Request $request,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService
    ): Response {
        $term = $request->query->get('q', '');
        $produits = [];
        
        if (strlen($term) >= 2) {
            $produits = $produitRepo->findBySearchTerm($term);
        }

        $categories = $categorieRepo->findAll();
        $cartCount = $cartService->getCartCount();

        return $this->render('catalogue/index.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'searchTerm' => $term,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/search-ajax', name: 'app_catalogue_search_ajax', methods: ['GET'])]
    public function searchAjax(
        Request $request,
        ProduitRepository $produitRepo
    ): Response {
        $term = $request->query->get('q', '');
        $produits = [];
        
        if (strlen($term) >= 2) {
            $produits = $produitRepo->findBySearchTerm($term);
        }

        return $this->render('catalogue/_product_list.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/{id}', name: 'app_product_detail', methods: ['GET'])]
    public function detail(
        Produit $produit,
        CartService $cartService
    ): Response {
        $cartCount = $cartService->getCartCount();

        return $this->render('catalogue/product_detail.html.twig', [
            'produit' => $produit,
            'cartCount' => $cartCount,
        ]);
    }
}
