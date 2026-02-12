<?php

namespace App\Controller;

use App\Entity\CommandeProduit;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    #[Route('/{id}', name: 'app_payment', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function payment(CommandeProduit $commande): Response
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $_SERVER['STRIPE_SECRET_KEY'] ?? null;

        return $this->render('order/payment.html.twig', [
            'commande' => $commande,
            'stripeConfigured' => (bool) $stripeSecret,
        ]);
    }

    #[Route('/{id}/create-session', name: 'app_payment_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createSession(
        CommandeProduit $commande,
        Request $request,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? $_SERVER['STRIPE_SECRET_KEY'] ?? null;
        if (!$stripeSecret) {
            return new JsonResponse(['error' => 'Stripe non configuré (STRIPE_SECRET_KEY manquant)'], 500);
        }

        if (!class_exists('\\Stripe\\StripeClient')) {
            return new JsonResponse(['error' => 'stripe/stripe-php non installé. Exécutez: composer require stripe/stripe-php'], 500);
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);

        $lineItems = [];
        foreach ($commande->getLignesCommande() as $ligne) {
            $product = $ligne->getProduit();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $product->getNom()],
                    'unit_amount' => (int) round((float) $ligne->getPrixUnitaire() * 100),
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
                'metadata' => ['order_id' => (string) $commande->getId()],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => 'Erreur Stripe: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur de paiement. Veuillez réessayer.'], 500);
        }

        return new JsonResponse(['url' => $session->url]);
    }

    #[Route('/{id}/success', name: 'app_payment_success', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function success(
        CommandeProduit $commande,
        CartService $cartService
    ): Response {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $cartService->clearCart();
        return $this->render('order/payment_success.html.twig', ['commande' => $commande]);
    }

    #[Route('/{id}/cancel', name: 'app_payment_cancel', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function cancel(CommandeProduit $commande): Response
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('order/payment_cancel.html.twig', ['commande' => $commande]);
    }
}
