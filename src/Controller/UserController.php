<?php

namespace App\Controller;

use App\Form\ProfileSettingsFormType;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger,
        private string $photosDirectory
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/voir/{id}', name: 'app_user_profile_view', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profileView(int $id): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'is_own_profile' => $user === $this->getUser(),
        ]);
    }

    #[Route('/parametres', name: 'app_settings', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function settings(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileSettingsFormType::class, $user, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $existing = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $newEmail]);
                if ($existing) {
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
                    return $this->render('user/settings.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            // Gérer l'upload de photo si présent
            $photoFile = $form->get('photo')->getData();
            if ($photoFile instanceof UploadedFile) {
                // Supprimer l'ancienne photo si elle existe
                if ($user->getPhoto()) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getPhoto();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
                
                $photoPath = $this->handlePhotoUpload($photoFile);
                if ($photoPath) {
                    $user->setPhoto($photoPath);
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo. Veuillez réessayer.');
                    return $this->render('user/settings.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Vos paramètres ont été enregistrés avec succès.');
            return $this->redirectToRoute('app_settings');
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    private function handlePhotoUpload(UploadedFile $file): ?string
    {
        // Vérifier le type MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return null;
        }

        // Vérifier la taille (5 Mo max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->photosDirectory)) {
            mkdir($this->photosDirectory, 0755, true);
        }

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->toString();
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->photosDirectory, $newFilename);
            // Retourner le chemin relatif depuis public/
            return 'uploads/photos/' . $newFilename;
        } catch (FileException $e) {
            error_log('[MediConnect] Erreur upload photo: ' . $e->getMessage());
            return null;
        }
    }
}
