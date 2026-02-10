<?php

namespace App\Service;

use App\Entity\Produit;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    const CART_SESSION_KEY = 'cart_items';

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function addToCart(Produit $produit, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $productId = $produit->getId();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'id' => $productId,
                'nom' => $produit->getNom(),
                'prix' => $produit->getPrix(),
                'image' => $produit->getImage(),
                'quantity' => $quantity,
            ];
        }

        $this->saveCart($cart);
    }

    public function removeFromCart(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->saveCart($cart);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                $this->removeFromCart($productId);
            } else {
                $cart[$productId]['quantity'] = $quantity;
                $this->saveCart($cart);
            }
        }
    }

    public function getCart(): array
    {
        return $this->requestStack->getSession()->get(self::CART_SESSION_KEY, []);
    }

    public function getCartCount(): int
    {
        $cart = $this->getCart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public function getCartTotal(): float
    {
        $cart = $this->getCart();
        $total = 0;
        foreach ($cart as $item) {
            $total += (float)$item['prix'] * $item['quantity'];
        }
        return $total;
    }

    public function clearCart(): void
    {
        $this->requestStack->getSession()->set(self::CART_SESSION_KEY, []);
    }

    private function saveCart(array $cart): void
    {
        $this->requestStack->getSession()->set(self::CART_SESSION_KEY, $cart);
    }
}
