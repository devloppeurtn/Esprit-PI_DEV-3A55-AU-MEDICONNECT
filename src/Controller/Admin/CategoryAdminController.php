<?php

namespace App\Controller\Admin;

use App\Entity\CategorieProduit;
use App\Repository\CategorieProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/categories')]
class CategoryAdminController extends AbstractController
{
    #[Route('/', name: 'admin_categories')]
    public function index(CategorieProduitRepository $repo): Response
    {
        return $this->render('admin/categories/index.html.twig', [
            'categories' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_categories_new')]
    #[Route('/{id}/edit', name: 'admin_categories_edit', requirements: ['id' => '\d+'])]
    public function form(Request $request, EntityManagerInterface $em, ?CategorieProduit $categorie = null): Response
    {
        $categorie = $categorie ?? new CategorieProduit();

        if ($request->isMethod('POST')) {
            $categorie->setNom(trim((string) $request->request->get('nom', '')));
            $categorie->setDescription($request->request->get('description'));

            $manualImagePath = trim((string) $request->request->get('image', ''));
            if ($manualImagePath !== '') {
                $categorie->setImage($manualImagePath);
            }

            $imageFile = $request->files->get('image_file');
            if ($imageFile instanceof UploadedFile) {
                $oldImage = $categorie->getImage();
                $uploadedPath = $this->uploadImage($imageFile, 'categories');

                if ($uploadedPath === null) {
                    $this->addFlash('error', 'Image upload failed.');
                    return $this->redirectToRoute($categorie->getId() ? 'admin_categories_edit' : 'admin_categories_new', $categorie->getId() ? ['id' => $categorie->getId()] : []);
                }

                $categorie->setImage($uploadedPath);
                if ($oldImage !== $uploadedPath) {
                    $this->deleteLocalImage($oldImage);
                }
            }

            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', 'Categorie enregistree.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => $categorie,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_categories_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CategorieProduit $categorie, EntityManagerInterface $em): Response
    {
        $filesToDelete = [];

        if ($categorie->getImage()) {
            $filesToDelete[] = $categorie->getImage();
        }

        foreach ($categorie->getProduits()->toArray() as $produit) {
            if ($produit->getImage()) {
                $filesToDelete[] = $produit->getImage();
            }

            foreach ($produit->getLignesCommande()->toArray() as $ligne) {
                $em->remove($ligne);
            }

            foreach ($produit->getAvisProduits()->toArray() as $avis) {
                $em->remove($avis);
            }

            $em->remove($produit);
        }

        $em->remove($categorie);
        $em->flush();

        foreach (array_unique($filesToDelete) as $path) {
            $this->deleteLocalImage($path);
        }

        $this->addFlash('success', 'Categorie supprimee avec ses produits.');
        return $this->redirectToRoute('admin_categories');
    }

    private function uploadImage(UploadedFile $file, string $folder): ?string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $originalName) ?: 'image';
        $safeName = trim($safeName, '-');
        if ($safeName === '') {
            $safeName = 'image';
        }

        $extension = $file->guessExtension() ?: ($file->getClientOriginalExtension() ?: 'bin');
        $fileName = sprintf('%s-%s.%s', $safeName, uniqid('', true), $extension);
        $relativeDir = 'uploads/' . $folder;
        $targetDir = $this->getParameter('kernel.project_dir') . '/public/' . $relativeDir;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return null;
        }

        try {
            $file->move($targetDir, $fileName);
        } catch (FileException) {
            return null;
        }

        return $relativeDir . '/' . $fileName;
    }

    private function deleteLocalImage(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'uploads/')) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
