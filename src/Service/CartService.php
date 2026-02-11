<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\PromoCodeRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public const CART_SESSION_KEY = 'cart_items';
    public const PROMO_SESSION_KEY = 'cart_promo';

    public function __construct(
        private RequestStack $requestStack,
        private PromoCodeRepository $promoRepo
    ) {
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
        $this->clearPromo();
    }

    public function applyPromo(string $code): bool
    {
        $normalized = strtoupper(trim($code));
        $promo = $this->promoRepo->findActiveByCode($normalized);
        if ($promo) {
            if ($promo->getUsageLimit() !== null && $promo->getUsedCount() >= $promo->getUsageLimit()) {
                $this->clearPromo();
                return false;
            }
            $rate = (float) $promo->getRate() / 100;
            $this->requestStack->getSession()->set(self::PROMO_SESSION_KEY, [
                'code' => $promo->getCode(),
                'rate' => $rate,
            ]);
            return true;
        }
        $this->clearPromo();
        return false;
    }

    public function clearPromo(): void
    {
        $this->requestStack->getSession()->remove(self::PROMO_SESSION_KEY);
    }

    public function getPromo(): array
    {
        return $this->requestStack->getSession()->get(self::PROMO_SESSION_KEY, []);
    }

    public function getDiscountAmount(?float $subtotal = null): float
    {
        $promo = $this->getPromo();
        if (empty($promo)) {
            return 0.0;
        }
        $subtotal = $subtotal ?? $this->getCartTotal();
        return round($subtotal * ($promo['rate'] ?? 0), 2);
    }

    public function getFinalTotal(): float
    {
        $subtotal = $this->getCartTotal();
        $discount = $this->getDiscountAmount($subtotal);
        return max(0, $subtotal - $discount);
    }

    public function getTotals(): array
    {
        $subtotal = $this->getCartTotal();
        $discount = $this->getDiscountAmount($subtotal);
        $total = max(0, $subtotal - $discount);
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'promo' => $this->getPromo(),
        ];
    }

    private function saveCart(array $cart): void
    {
        $this->requestStack->getSession()->set(self::CART_SESSION_KEY, $cart);
    }
}
