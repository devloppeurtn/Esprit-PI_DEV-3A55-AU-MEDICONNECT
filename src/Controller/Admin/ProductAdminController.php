<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Entity\CategorieProduit;
use App\Repository\ProduitRepository;
use App\Repository\CategorieProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products')]
class ProductAdminController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(ProduitRepository $repo): Response
    {
        return $this->render('admin/products/index.html.twig', [
            'products' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_products_new')]
    #[Route('/{id}/edit', name: 'admin_products_edit', requirements: ['id' => '\d+'])]
    public function form(
        Request $request,
        EntityManagerInterface $em,
        CategorieProduitRepository $catRepo,
        ?Produit $produit = null
    ): Response {
        $produit = $produit ?? new Produit();

        $produit->setStock($produit->getStock() ?? 0);
        $produit->setPrix($produit->getPrix() ?? '0.00');

        if ($request->isMethod('POST')) {
            $produit->setNom($request->request->get('nom', ''));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix($request->request->get('prix', '0'));
            $produit->setStock((int)$request->request->get('stock', 0));
            $produit->setImage($request->request->get('image') ?: null);

            $catId = (int)$request->request->get('categorie');
            $categorie = $catRepo->find($catId);
            if ($categorie) {
                $produit->setCategorie($categorie);
            }

            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/products/form.html.twig', [
            'product' => $produit,
            'categories' => $catRepo->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_products_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('admin_products');
    }
}
