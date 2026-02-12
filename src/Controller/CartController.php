<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cartService, ProduitRepository $produitRepo): Response
    {
        $cart = $cartService->getCart();
        $cartTotals = $cartService->getTotals();
        $cartCount = $cartService->getCartCount();

        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $products = $produitRepo->findBy(['id' => $productIds]);
            foreach ($products as $product) {
                $productId = $product->getId();
                if (isset($cart[$productId])) {
                    $cart[$productId]['image'] = $product->getImage();
                }
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cartTotals' => $cartTotals,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(
        int $id,
        Request $request,
        ProduitRepository $produitRepo,
        CartService $cartService
    ): JsonResponse {
        $produit = $produitRepo->find($id);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }
        $quantity = $request->request->getInt('quantity', 1);
        if ($quantity <= 0 || $quantity > $produit->getStock()) {
            return new JsonResponse(['error' => 'Stock insuffisant pour ce produit'], 400);
        }
        $cartService->addToCart($produit, $quantity);
        $totals = $cartService->getTotals();

        return new JsonResponse([
            'success' => true,
            'message' => $produit->getNom() . ' ajouté au panier',
            'cartCount' => $cartService->getCartCount(),
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
        ]);
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, CartService $cartService): JsonResponse
    {
        $quantity = $request->request->getInt('quantity', 1);
        if ($quantity <= 0) {
            $cartService->removeFromCart($id);
            $message = 'Produit retiré du panier';
        } else {
            $cartService->updateQuantity($id, $quantity);
            $message = 'Panier mis à jour';
        }
        $cart = $cartService->getCart();
        $cartItem = $cart[$id] ?? null;
        $itemTotal = $cartItem ? (float)$cartItem['prix'] * $quantity : 0;
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

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(int $id, CartService $cartService): JsonResponse
    {
        $cartService->removeFromCart($id);
        $totals = $cartService->getTotals();

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit retiré du panier',
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

        $promo = $totals['promo'];
        $ratePct = !empty($promo['rate']) ? (int) round($promo['rate'] * 100) : 0;
        $promoMessage = 'Code promo appliqué.' . ($ratePct ? " (-{$ratePct}%)" : '');

        return new JsonResponse([
            'success' => true,
            'message' => $promoMessage,
            'cartTotal' => number_format($totals['total'], 2, '.', ''),
            'subtotal' => number_format($totals['subtotal'], 2, '.', ''),
            'discount' => number_format($totals['discount'], 2, '.', ''),
            'promo' => $totals['promo'],
        ]);
    }
}
