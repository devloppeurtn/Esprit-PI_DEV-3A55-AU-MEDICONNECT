<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cartService, ProduitRepository $produitRepo): Response
    {
        $cart = $cartService->getCart();
        $cartTotals = $cartService->getTotals();
        $cartCount = $cartService->getCartCount();
        $stockIssues = [];
        $hasStockIssue = false;

        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $products = $produitRepo->findBy(['id' => $productIds]);
            $productsById = [];

            foreach ($products as $product) {
                $productsById[$product->getId()] = $product;
            }

            foreach ($cart as $productId => $item) {
                $product = $productsById[(int) $productId] ?? null;
                if ($product === null) {
                    $cart[$productId]['stock'] = 0;
                    $stockIssues[$productId] = 'Produit indisponible.';
                    $hasStockIssue = true;
                    continue;
                }

                $stock = max(0, (int) $product->getStock());
                $cart[$productId]['image'] = $product->getImage();
                $cart[$productId]['stock'] = $stock;

                if ($stock <= 0) {
                    $stockIssues[$productId] = 'Rupture de stock.';
                    $hasStockIssue = true;
                    continue;
                }

                if ((int) $item['quantity'] > $stock) {
                    $stockIssues[$productId] = sprintf(
                        'Stock disponible: %d, quantite dans panier: %d.',
                        $stock,
                        (int) $item['quantity']
                    );
                    $hasStockIssue = true;
                }
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cartTotals' => $cartTotals,
            'cartCount' => $cartCount,
            'hasStockIssue' => $hasStockIssue,
            'stockIssues' => $stockIssues,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProduitRepository $produitRepo,
        CartService $cartService
    ): JsonResponse {
        $produit = $produitRepo->find($id);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouve'], 404);
        }

        $quantity = $request->request->getInt('quantity', 1);
        $stock = max(0, (int) $produit->getStock());
        $currentInCart = $cartService->getProductQuantity($id);

        if ($quantity <= 0) {
            return new JsonResponse([
                'error' => 'Quantite invalide',
            ], 400);
        }

        if ($stock <= 0) {
            return new JsonResponse([
                'error' => 'Produit en rupture de stock',
            ], 400);
        }

        if (($currentInCart + $quantity) > $stock) {
            return new JsonResponse([
                'error' => sprintf('Stock insuffisant. Maximum disponible: %d.', $stock),
                'maxStock' => $stock,
                'currentInCart' => $currentInCart,
            ], 400);
        }

        $cartService->addToCart($produit, $quantity);
        $totals = $cartService->getTotals();

        return new JsonResponse([
            'success' => true,
            'message' => $produit->getNom() . ' ajoute au panier',
            'cartCount' => $cartService->getCartCount(),
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
        ]);
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        ProduitRepository $produitRepo,
        CartService $cartService
    ): JsonResponse {
        $quantity = $request->request->getInt('quantity', 1);

        if ($quantity <= 0) {
            $cartService->removeFromCart($id);
            $message = 'Produit retire du panier';
        } else {
            $produit = $produitRepo->find($id);
            if (!$produit) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Produit introuvable',
                ], 404);
            }

            $stock = max(0, (int) $produit->getStock());
            if ($stock <= 0) {
                $cartService->removeFromCart($id);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Produit en rupture de stock. Il a ete retire du panier.',
                    'maxStock' => 0,
                    'removed' => true,
                ], 400);
            }

            if ($quantity > $stock) {
                return new JsonResponse([
                    'success' => false,
                    'error' => sprintf('Stock insuffisant. Maximum disponible: %d.', $stock),
                    'maxStock' => $stock,
                ], 400);
            }

            $cartService->updateQuantity($id, $quantity);
            $message = 'Panier mis a jour';
        }

        $cart = $cartService->getCart();
        $cartItem = $cart[$id] ?? null;
        $itemTotal = 0.0;

        if ($cartItem) {
            $itemTotal = (float) $cartItem['prix'] * (int) ($cartItem['quantity'] ?? $quantity);
        }

        $totals = $cartService->getTotals();

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'itemTotal' => number_format($itemTotal, 2, '.', ''),
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        int $id,
        CartService $cartService
    ): JsonResponse {
        $cartService->removeFromCart($id);
        $totals = $cartService->getTotals();

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit retire du panier',
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartService $cartService): JsonResponse
    {
        $cartService->clearCart();

        return new JsonResponse([
            'success' => true,
            'message' => 'Panier vide',
            'cartCount' => 0,
            'cartTotal' => '0.00',
            'subtotal' => '0.00',
            'discount' => '0.00',
            'promo' => [],
        ]);
    }

    #[Route('/promo', name: 'app_cart_promo', methods: ['POST'])]
    public function applyPromo(Request $request, CartService $cartService): JsonResponse
    {
        $code = $request->request->get('code', '');
        $applied = $cartService->applyPromo($code);
        $totals = $cartService->getTotals();

        if (!$applied) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Code promo invalide',
                'cartTotal' => number_format($totals['total'], 2, '.', ''),
                'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
                'discount' => number_format($totals['discount'], 2, '.', ''),
                'promo' => $totals['promo'],
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Code promo applique (-20%)',
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
        ]);
    }
}
