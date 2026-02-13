<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Repository\CategorieProduitRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class ProductAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_products', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $repo,
        CategorieProduitRepository $catRepo
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $categoryId = $request->query->get('categorie');
        $activeCategoryId = null;
        if ($categoryId !== null && $categoryId !== '') {
            $activeCategoryId = (int) $categoryId;
        }

        $qb = $repo->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->orderBy('p.id', 'ASC');

        if ($search !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE :q OR LOWER(p.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        if ($activeCategoryId !== null) {
            $qb->andWhere('c.id = :catId')
               ->setParameter('catId', $activeCategoryId);
        }

        $products = $qb->getQuery()->getResult();

        return $this->render('admin/products/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'categories' => $catRepo->findAll(),
            'activeCategoryId' => $activeCategoryId,
        ]);
    }

    #[Route('/new', name: 'app_admin_products_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_admin_products_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(
        Request $request,
        EntityManagerInterface $em,
        CategorieProduitRepository $catRepo,
        ?Produit $produit = null
    ): Response {
        $produit = $produit ?? new Produit();

        if ($request->isMethod('POST')) {
            $produit->setNom($request->request->get('nom', ''));
            $produit->setDescription($request->request->get('description'));

            // Récupérer exactement ce que l'utilisateur a saisi
            $prixInput = trim((string) $request->request->get('prix', ''));
            $stockInput = trim((string) $request->request->get('stock', ''));

            // Normaliser le prix: remplacer virgule par point, puis forcer 2 décimales
            if ($prixInput !== '') {
                $prixNormalized = str_replace(',', '.', $prixInput);
                $prixDecimal = number_format((float) $prixNormalized, 2, '.', '');
            } else {
                $prixDecimal = '0.00';
            }

            // Stock: entier (si vide → 0)
            $stockValue = $stockInput !== '' ? (int) $stockInput : 0;

            $produit->setPrix($prixDecimal);
            $produit->setStock($stockValue);

            // Gestion de l'upload d'image
            $imageFile = $request->files->get('imageFile');
            if ($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $imageFile->isValid()) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $uploadDir = $projectDir . '/public/uploads/products';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                $originalName = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName) ?: 'product';
                $newFilename = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                // On stocke un chemin relatif utilisable par asset()
                $produit->setImage('uploads/products/' . $newFilename);
            } else {
                // Aucune nouvelle image uploadée : on conserve l'ancienne valeur éventuelle
                $current = $request->request->get('current_image');
                $produit->setImage($current !== '' ? $current : null);
            }

            // Gestion de la vidéo
            $videoFile = $request->files->get('videoFile');
            if ($videoFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $videoFile->isValid()) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $videoDir = $projectDir . '/public/uploads/videos';
                if (!is_dir($videoDir)) {
                    @mkdir($videoDir, 0775, true);
                }
                $originalName = pathinfo((string) $videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName) ?: 'video';
                $newFilename = $safeName . '-' . uniqid() . '.' . $videoFile->guessExtension();
                $videoFile->move($videoDir, $newFilename);
                $produit->setVideoUrl('uploads/videos/' . $newFilename);
            } else {
                // Pas de nouveau fichier, on prend éventuellement le lien externe
                $videoUrl = $request->request->get('video_url');
                $produit->setVideoUrl($videoUrl ?: $produit->getVideoUrl());
            }

            $catId = (int)$request->request->get('categorie');
            $categorie = $catRepo->find($catId);

            if (!$categorie) {
                // Si pour une raison quelconque la catégorie envoyée est invalide,
                // on prend la première catégorie existante comme valeur de secours.
                $categorie = $catRepo->findOneBy([]);
            }

            if ($categorie) {
                $produit->setCategorie($categorie);
            } else {
                // Cas très rare: aucune catégorie en base
                $this->addFlash('error', 'Aucune catégorie n\'est disponible. Créez d\'abord une catégorie.');
                return $this->redirectToRoute('app_admin_categories');
            }

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré.');
            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/products/form.html.twig', [
            'product' => $produit,
            'categories' => $catRepo->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_products_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Produit $produit, EntityManagerInterface $em): Response
    {
        // Empêcher la suppression si le produit est lié à des lignes de commande
        if ($produit->getLignesCommande()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce produit car il est déjà utilisé dans une ou plusieurs commandes.');
            return $this->redirectToRoute('app_admin_products');
        }

        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('app_admin_products');
    }
}
