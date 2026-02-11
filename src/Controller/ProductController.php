<?php

namespace App\Controller;

use App\Entity\AvisProduit;
use App\Entity\Utilisateur;
use App\Repository\AvisProduitRepository;
use App\Repository\CategorieProduitRepository;
use App\Repository\ProduitRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/categorie/{id}', name: 'app_catalogue_categorie', methods: ['GET'], requirements: ['id' => '\d+'])]
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
        $produits = strlen($term) >= 2 ? $produitRepo->findBySearchTerm($term) : [];
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
    public function searchAjax(Request $request, ProduitRepository $produitRepo): Response
    {
        $term = $request->query->get('q', '');
        $produits = strlen($term) >= 2 ? $produitRepo->findBySearchTerm($term) : [];

        return $this->render('catalogue/_product_list.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/{id}', name: 'app_product_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        ProduitRepository $produitRepo,
        CartService $cartService,
        AvisProduitRepository $avisProduitRepo
    ): Response {
        $produit = $produitRepo->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        $cartCount = $cartService->getCartCount();
        $avis = $avisProduitRepo->findByProduitOrdered($produit);
        $moyenneNote = $avisProduitRepo->getAverageForProduit($produit);

        return $this->render('catalogue/product_detail.html.twig', [
            'produit' => $produit,
            'cartCount' => $cartCount,
            'avis' => $avis,
            'moyenneNote' => $moyenneNote,
        ]);
    }

    #[Route('/{id}/avis', name: 'app_product_review_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addReview(
        int $id,
        Request $request,
        ProduitRepository $produitRepo,
        AvisProduitRepository $avisProduitRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $produit = $produitRepo->find($id);
        if (!$produit) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('product_review_' . $produit->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }
        $note = $request->request->getInt('note', 0);
        $commentaire = trim((string) $request->request->get('commentaire', ''));
        if ($note < 1 || $note > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }
        if (mb_strlen($commentaire) > 1000) {
            $this->addFlash('error', 'Le commentaire est trop long (max 1000 caractères).');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $avis = $avisProduitRepo->findOneBy(['produit' => $produit, 'utilisateur' => $user]);
        if (!$avis) {
            $avis = new AvisProduit();
            $avis->setProduit($produit);
            $avis->setUtilisateur($user);
            $entityManager->persist($avis);
        }
        $avis->setNote($note);
        $avis->setCommentaire($commentaire !== '' ? $commentaire : null);
        $avis->setDateCreation(new \DateTimeImmutable());
        $entityManager->flush();
        $this->addFlash('success', 'Votre avis a été enregistré.');
        return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
    }

    #[Route('/{id}/avis/{avisId}/supprimer', name: 'app_product_review_delete', methods: ['POST'], requirements: ['id' => '\d+', 'avisId' => '\d+'])]
    public function deleteReview(
        int $id,
        int $avisId,
        Request $request,
        ProduitRepository $produitRepo,
        AvisProduitRepository $avisProduitRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $produit = $produitRepo->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        $avis = $avisProduitRepo->find($avisId);
        if (!$avis || $avis->getProduit()?->getId() !== $produit->getId()) {
            throw $this->createNotFoundException('Avis non trouvé.');
        }
        if (!$this->isCsrfTokenValid('delete_review_' . $avis->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }
        $user = $this->getUser();
        $isOwner = $avis->getUtilisateur()?->getId() === $user?->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isOwner && !$isAdmin) {
            throw $this->createAccessDeniedException('Action non autorisée.');
        }
        $entityManager->remove($avis);
        $entityManager->flush();
        $this->addFlash('success', 'Avis supprimé.');
        return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
    }
}
