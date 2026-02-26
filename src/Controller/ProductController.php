<?php

namespace App\Controller;

use App\Entity\AvisProduit;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\AvisProduitRepository;
use App\Repository\CategorieProduitRepository;
use App\Repository\ProduitRepository;
use App\Service\CartService;
use App\Service\ProductPricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/catalogue')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_catalogue', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService,
        ProductPricingService $pricingService
    ): Response {
        $filters = $this->extractFilters($request);

        return $this->renderCatalogue(
            $filters,
            $produitRepo,
            $categorieRepo,
            $cartService,
            $pricingService
        );
    }

    #[Route('/categorie/{id}', name: 'app_catalogue_categorie', methods: ['GET'])]
    public function byCategorie(
        int $id,
        Request $request,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService,
        ProductPricingService $pricingService
    ): Response {
        $categorie = $categorieRepo->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Categorie non trouvee');
        }

        $filters = $this->extractFilters($request, $id);

        return $this->renderCatalogue(
            $filters,
            $produitRepo,
            $categorieRepo,
            $cartService,
            $pricingService
        );
    }

    #[Route('/search', name: 'app_catalogue_search', methods: ['GET'])]
    public function search(
        Request $request,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService,
        ProductPricingService $pricingService
    ): Response {
        $filters = $this->extractFilters($request);

        return $this->renderCatalogue(
            $filters,
            $produitRepo,
            $categorieRepo,
            $cartService,
            $pricingService
        );
    }

    #[Route('/search-ajax', name: 'app_catalogue_search_ajax', methods: ['GET'])]
    public function searchAjax(
        Request $request,
        ProduitRepository $produitRepo,
        ProductPricingService $pricingService
    ): Response {
        $filters = $this->extractFilters($request);
        $produits = $produitRepo->findByFilters($filters);

        return $this->render('catalogue/_product_list.html.twig', [
            'produits' => $produits,
            'dynamicPrices' => $this->buildDynamicPrices($produits, $pricingService),
        ]);
    }

    #[Route('/{id}', name: 'app_product_detail', methods: ['GET'])]
    public function detail(
        Produit $produit,
        CartService $cartService,
        AvisProduitRepository $avisProduitRepo,
        ProductPricingService $pricingService
    ): Response {
        $cartCount = $cartService->getCartCount();
        $avis = $avisProduitRepo->findByProduitOrdered($produit);
        $moyenneNote = $avisProduitRepo->getAverageForProduit($produit);
        $pricing = $pricingService->calculateForProduct($produit);

        return $this->render('catalogue/product_detail.html.twig', [
            'produit' => $produit,
            'cartCount' => $cartCount,
            'avis' => $avis,
            'moyenneNote' => $moyenneNote,
            'dynamicPrice' => $pricing,
        ]);
    }

    #[Route('/{id}/avis', name: 'app_product_review_add', methods: ['POST'])]
    public function addReview(
        Produit $produit,
        Request $request,
        AvisProduitRepository $avisProduitRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid(
            'product_review_' . $produit->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }

        $note = $request->request->getInt('note', 0);
        $commentaire = trim((string) $request->request->get('commentaire', ''));

        if ($note < 1 || $note > 5) {
            $this->addFlash('error', 'La note doit etre comprise entre 1 et 5.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }

        if (mb_strlen($commentaire) > 1000) {
            $this->addFlash('error', 'Le commentaire est trop long (max 1000 caracteres).');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        $avis = $avisProduitRepo->findOneBy([
            'produit' => $produit,
            'utilisateur' => $utilisateur,
        ]);

        if (!$avis) {
            $avis = new AvisProduit();
            $avis->setProduit($produit);
            $avis->setUtilisateur($utilisateur);
            $entityManager->persist($avis);
        }

        $avis->setNote($note);
        $avis->setCommentaire($commentaire !== '' ? $commentaire : null);
        $avis->setDateCreation(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Votre avis a ete enregistre.');
        return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
    }

    #[Route('/{id}/avis/{avisId}/supprimer', name: 'app_product_review_delete', methods: ['POST'])]
    public function deleteReview(
        Produit $produit,
        int $avisId,
        Request $request,
        AvisProduitRepository $avisProduitRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $avis = $avisProduitRepo->find($avisId);
        if (!$avis || $avis->getProduit()?->getId() !== $produit->getId()) {
            throw $this->createNotFoundException('Avis non trouve.');
        }

        if (!$this->isCsrfTokenValid(
            'delete_review_' . $avis->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
        }

        $user = $this->getUser();
        $isOwner = $avis->getUtilisateur()?->getId() === $user?->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isAdmin) {
            throw $this->createAccessDeniedException('Action non autorisee.');
        }

        $entityManager->remove($avis);
        $entityManager->flush();

        $this->addFlash('success', 'Avis supprime.');
        return $this->redirectToRoute('app_product_detail', ['id' => $produit->getId()]);
    }

    private function renderCatalogue(
        array $filters,
        ProduitRepository $produitRepo,
        CategorieProduitRepository $categorieRepo,
        CartService $cartService,
        ProductPricingService $pricingService
    ): Response {
        $categories = $categorieRepo->findAll();
        $produits = $produitRepo->findByFilters($filters);
        $categorieActive = null;

        if ($filters['categorieId'] !== null) {
            $categorieActive = $categorieRepo->find($filters['categorieId']);
        }

        return $this->render('catalogue/index.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'categorieActive' => $categorieActive,
            'cartCount' => $cartService->getCartCount(),
            'filters' => $filters,
            'dynamicPrices' => $this->buildDynamicPrices($produits, $pricingService),
        ]);
    }

    private function buildDynamicPrices(array $produits, ProductPricingService $pricingService): array
    {
        return $pricingService->calculateForProducts($produits);
    }

    private function extractFilters(Request $request, ?int $forcedCategorieId = null): array
    {
        $sort = (string) $request->query->get('sort', 'name_asc');
        $allowedSorts = ['name_asc', 'name_desc', 'price_asc', 'price_desc', 'stock_desc', 'newest'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name_asc';
        }

        $categorieId = $forcedCategorieId;
        if ($categorieId === null) {
            $rawCategorie = $request->query->get('categorie');
            if ($rawCategorie !== null && $rawCategorie !== '') {
                $categorieId = max(1, (int) $rawCategorie);
            }
        }

        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        $minPrice = ($minPrice !== null && $minPrice !== '') ? (float) $minPrice : null;
        $maxPrice = ($maxPrice !== null && $maxPrice !== '') ? (float) $maxPrice : null;

        if ($minPrice !== null && $minPrice < 0) {
            $minPrice = 0.0;
        }
        if ($maxPrice !== null && $maxPrice < 0) {
            $maxPrice = null;
        }
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'q' => trim((string) $request->query->get('q', '')),
            'categorieId' => $categorieId,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'inStock' => $request->query->getBoolean('inStock', false),
            'sort' => $sort,
        ];
    }
}
