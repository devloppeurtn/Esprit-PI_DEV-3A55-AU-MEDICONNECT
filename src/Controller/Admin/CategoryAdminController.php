<?php

namespace App\Controller\Admin;

use App\Entity\CategorieProduit;
use App\Repository\CategorieProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_categories', methods: ['GET'])]
    public function index(CategorieProduitRepository $repo): Response
    {
        return $this->render('admin/categories/index.html.twig', [
            'categories' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_categories_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_admin_categories_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, EntityManagerInterface $em, ?CategorieProduit $categorie = null): Response
    {
        $categorie = $categorie ?? new CategorieProduit();

        if ($request->isMethod('POST')) {
            $categorie->setNom($request->request->get('nom', ''));
            $categorie->setDescription($request->request->get('description'));
            $categorie->setImage($request->request->get('image') ?: null);

            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', 'Catégorie enregistrée.');
            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => $categorie,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_categories_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CategorieProduit $categorie, EntityManagerInterface $em): Response
    {
        $em->remove($categorie);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('app_admin_categories');
    }
}
