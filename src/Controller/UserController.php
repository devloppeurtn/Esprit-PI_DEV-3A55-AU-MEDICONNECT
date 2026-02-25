<?php

namespace App\Controller;

use App\Form\ProfileSettingsFormType;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
<<<<<<< HEAD
=======
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
>>>>>>> isramedi
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
<<<<<<< HEAD
=======
use Symfony\Component\String\Slugger\SluggerInterface;
>>>>>>> isramedi

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
<<<<<<< HEAD
=======
        private SluggerInterface $slugger,
        private string $photosDirectory
>>>>>>> isramedi
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

<<<<<<< HEAD
=======
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

>>>>>>> isramedi
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
<<<<<<< HEAD
            // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
=======
            // VÃ©rifier que l'email n'est pas dÃ©jÃ  utilisÃ© par un autre utilisateur
>>>>>>> isramedi
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $existing = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $newEmail]);
                if ($existing) {
<<<<<<< HEAD
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
=======
                    $this->addFlash('error', 'Cet email est dÃ©jÃ  utilisÃ© par un autre compte.');
                    return $this->render('user/settings.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            // GÃ©rer l'upload de photo si prÃ©sent
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
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo. Veuillez rÃ©essayer.');
>>>>>>> isramedi
                    return $this->render('user/settings.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->flush();
<<<<<<< HEAD
            $this->addFlash('success', 'Vos paramètres ont été enregistrés avec succès.');
=======
            $this->addFlash('success', 'Vos paramÃ¨tres ont Ã©tÃ© enregistrÃ©s avec succÃ¨s.');
>>>>>>> isramedi
            return $this->redirectToRoute('app_settings');
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
<<<<<<< HEAD
}
=======

    private function handlePhotoUpload(UploadedFile $file): ?string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $clientMime = strtolower((string) $file->getClientMimeType());
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());

        if ($clientExtension === '' || !in_array($clientExtension, $allowedExtensions, true)) {
            return null;
        }

        if ($clientMime !== '' && !in_array($clientMime, $allowedMimes, true)) {
            return null;
        }

        // VÃ©rifier la taille (5 Mo max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        // CrÃ©er le rÃ©pertoire s'il n'existe pas
        if (!is_dir($this->photosDirectory)) {
            mkdir($this->photosDirectory, 0755, true);
        }

        // GÃ©nÃ©rer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->toString();
        if ($safeFilename === '') {
            $safeFilename = 'photo';
        }

        $extension = in_array($clientExtension, $allowedExtensions, true) ? $clientExtension : 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

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

>>>>>>> isramedi
