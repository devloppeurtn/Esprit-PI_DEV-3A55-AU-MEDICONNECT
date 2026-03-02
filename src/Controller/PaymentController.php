<?php

namespace App\Controller;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;
use App\Service\CartService;
use App\Service\StockReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    #[Route('/{id}', name: 'app_payment', methods: ['GET', 'POST'])]
    public function payment(
        CommandeProduit $commande,
        Request $request,
        EntityManagerInterface $em,
        CartService $cartService,
        StockReservationService $stockReservationService
    ): Response {
        // ensure the order belongs to current user
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($stockReservationService->releaseIfExpired($commande)) {
            $this->addFlash(
                'error',
                sprintf(
                    'Reservation expiree: le stock a ete libere apres %d minutes sans paiement.',
                    $stockReservationService->getReservationDurationMinutes()
                )
            );
            return $this->redirectToRoute('app_orders_list');
        }

        if ($commande->getStatut() !== StatutCommande::EN_ATTENTE) {
            return $this->redirectToRoute('app_order_detail', ['id' => $commande->getId()]);
        }

        // detect stripe secret presence so template can disable UI when missing
        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $_SERVER['STRIPE_SECRET_KEY'] ?? null;

        return $this->render('order/payment.html.twig', [
            'commande' => $commande,
            'stripeConfigured' => (bool) $stripeSecret,
        ]);
    }

    #[Route('/{id}/create-session', name: 'app_payment_create', methods: ['POST'])]
    public function createSession(
        CommandeProduit $commande,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        StockReservationService $stockReservationService
    ): JsonResponse {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Acces non autorise'], 403);
        }

        if ($stockReservationService->releaseIfExpired($commande) || $commande->getStatut() !== StatutCommande::EN_ATTENTE) {
            return new JsonResponse([
                'error' => sprintf(
                    'Reservation expiree (au-dela de %d minutes) ou commande non payable.',
                    $stockReservationService->getReservationDurationMinutes()
                ),
            ], 409);
        }

        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $_SERVER['STRIPE_SECRET_KEY'] ?? null;
        if (!$stripeSecret) {
            return new JsonResponse(['error' => 'Stripe not configured (STRIPE_SECRET_KEY missing)'], 500);
        }

        // Lazy-check for stripe-php
        if (!class_exists('\\Stripe\\StripeClient')) {
            return new JsonResponse(['error' => 'stripe/stripe-php not installed. Run: composer require stripe/stripe-php'], 500);
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);

        $lineItems = [];
        foreach ($commande->getLignesCommande() as $ligne) {
            $product = $ligne->getProduit();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $product->getNom()],
                    'unit_amount' => (int)round(($ligne->getPrixUnitaire() * 100)),
                ],
                'quantity' => $ligne->getQuantite(),
            ];
        }

        $successUrl = $urlGenerator->generate('app_payment_success', ['id' => $commande->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $urlGenerator->generate('app_payment_cancel', ['id' => $commande->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => ['order_id' => (string)$commande->getId()],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => 'Stripe error: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Payment error. Please try again.'], 500);
        }


        return new JsonResponse(['url' => $session->url]);
    }

    #[Route('/{id}/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(CommandeProduit $commande): Response
    {
        // Note: Prefer verifying payment via webhook in production. This page simply shows confirmation.
        return $this->render('order/payment_success.html.twig', ['commande' => $commande]);
    }

    #[Route('/{id}/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(CommandeProduit $commande): Response
    {
        return $this->render('order/payment_cancel.html.twig', ['commande' => $commande]);
    }
}
