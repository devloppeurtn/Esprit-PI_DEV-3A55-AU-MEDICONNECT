<?php

namespace App\Controller;

use App\Entity\CommandeProduit;
use App\Entity\LigneCommande;
use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use App\Repository\ProduitRepository;
use App\Service\CartService;
use App\Service\SmsNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commandes')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_orders_list', methods: ['GET'])]
    public function list(
        CommandeProduitRepository $commandeRepo,
        CartService $cartService
    ): Response {
        $user = $this->getUser();
        $commandes = $commandeRepo->findByUtilisateur($user);
        $cartCount = $cartService->getCartCount();

        return $this->render('order/list.html.twig', [
            'commandes' => $commandes,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/{id}', name: 'app_order_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(
        CommandeProduit $commande,
        CartService $cartService
    ): Response {
        // Check that the order belongs to the current user
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $cartCount = $cartService->getCartCount();

        return $this->render('order/detail.html.twig', [
            'commande' => $commande,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cartService,
        ProduitRepository $produitRepo,
        EntityManagerInterface $em,
        SmsNotifier $smsNotifier
    ): Response {
        $cart = $cartService->getCart();
        $user = $this->getUser();

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_catalogue');
        }

        if ($request->isMethod('POST')) {
            // Collect address parts
            $rue = trim($request->request->get('rue', ''));
            $complement = trim($request->request->get('complement', ''));
            $ville = trim($request->request->get('ville', ''));
            $codePostal = trim($request->request->get('code_postal', ''));
            $pays = trim($request->request->get('pays', ''));
            $telephoneRaw = trim($request->request->get('telephone', ''));
            $telephoneE164 = trim($request->request->get('telephone_e164', ''));
            $telephone = !empty($telephoneE164) ? $telephoneE164 : $telephoneRaw;

            if (empty($rue) || empty($telephone) || empty($pays)) {
                $this->addFlash('error', 'Veuillez renseigner au minimum la rue, le numéro de téléphone et le pays.');
                return $this->redirectToRoute('app_checkout');
            }

            $telephoneClean = preg_replace('/[^0-9+]/', '', $telephone);
            if (!preg_match('/^\+[1-9][0-9]{7,14}$/', $telephoneClean)) {
                $this->addFlash('error', 'Numéro de téléphone invalide. Format attendu: +21612345678 (8 à 15 chiffres).');
                return $this->redirectToRoute('app_checkout');
            }

            // Assemble full address
            $adresseParts = array_filter([$rue, $complement, $ville, $codePostal]);
            $adresseLivraison = implode(', ', $adresseParts);

            // Validate stock before creating order
            foreach ($cart as $item) {
                $produitCheck = $produitRepo->find($item['id']);
                if (!$produitCheck || $produitCheck->getStock() < $item['quantity']) {
                    $this->addFlash('error', 'Stock insuffisant pour ' . ($produitCheck?->getNom() ?? 'produit') . '.');
                    return $this->redirectToRoute('app_cart');
                }
            }

            // Create order (draft)
            $commande = new CommandeProduit();
            $commande->setUtilisateur($user);
            $commande->setDateCommande(new \DateTime());
            $commande->setStatut(StatutCommande::EN_ATTENTE);
            $commande->setAdresseLivraison($adresseLivraison);
            $commande->setTelephone($telephoneClean);
            $commande->setPays($pays);

            $totalAmount = 0;

            // Create line items from cart
            foreach ($cart as $item) {
                $produit = $produitRepo->find($item['id']);
                if (!$produit) {
                    continue;
                }

                $ligne = new LigneCommande();
                $ligne->setCommande($commande);
                $ligne->setProduit($produit);
                $ligne->setQuantite($item['quantity']);
                $ligne->setPrixUnitaire($item['prix']);

                $em->persist($ligne);
                $totalAmount += (float) $item['prix'] * $item['quantity'];

                // Decrement stock
                $remaining = max(0, $produit->getStock() - $item['quantity']);
                $produit->setStock($remaining);
                $em->persist($produit);
            }

            // Apply promo/discount if any
            $totals = $cartService->getTotals();
            $totalAmount = $totals['total'] ?? $totalAmount;

            $commande->setMontantTotal(number_format($totalAmount, 2, '.', ''));
            $em->persist($commande);
            $em->flush();

            $smsNotifier->sendOrderCreated($commande->getTelephone(), $commande);

            // Redirect to review page where user chooses payment method
            return $this->redirectToRoute('app_order_review', ['id' => $commande->getId()]);
        }

        $cartTotals = $cartService->getTotals();
        $cartCount = $cartService->getCartCount();

        return $this->render('order/checkout.html.twig', [
            'cart' => $cart,
            'cartTotals' => $cartTotals,
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/{id}/review', name: 'app_order_review', methods: ['GET','POST'], requirements: ['id' => '\d+'])]
    public function review(
        CommandeProduit $commande,
        Request $request,
        EntityManagerInterface $em,
        CartService $cartService
    ): Response {
        // ensure the order belongs to current user
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $paymentMethod = $request->request->get('payment_method', 'card');

            if ($paymentMethod === 'card') {
                // Redirect to the existing payment page (Stripe flow)
                return $this->redirectToRoute('app_payment', ['id' => $commande->getId()]);
            }

            if ($paymentMethod === 'cod') {
                // Cash on delivery: mark as prepared (business logic)
                $commande->setModePaiement('cod');
                $commande->setStatut(StatutCommande::PREPAREE);
                $em->persist($commande);
                $em->flush();

                // Clear cart
                $cartService->clearCart();

                $this->addFlash('success', 'Commande enregistrée. Paiement à la livraison sélectionné.');
                return $this->redirectToRoute('app_order_detail', ['id' => $commande->getId()]);
            }
        }

        return $this->render('order/review.html.twig', [
            'commande' => $commande,
        ]);
    }
}
