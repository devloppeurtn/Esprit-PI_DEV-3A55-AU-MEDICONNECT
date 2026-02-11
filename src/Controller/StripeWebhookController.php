<?php

namespace App\Controller;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/payment/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? null;

        if (!$endpointSecret) {
            // Without webhook secret we cannot verify; reject.
            return new Response('Webhook secret not configured', 400);
        }

        if (!class_exists('\\Stripe\\Webhook')) {
            return new Response('stripe/stripe-php not installed', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;
            if ($orderId) {
                $commande = $em->getRepository(CommandeProduit::class)->find((int)$orderId);
                if ($commande) {
                    $commande->setStatut(StatutCommande::VALIDEE);
                    $em->persist($commande);
                    $em->flush();
                }
            }
        }

        return new Response('ok', 200);
    }
}
