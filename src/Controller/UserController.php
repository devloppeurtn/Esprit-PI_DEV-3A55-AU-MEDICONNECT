<?php

namespace App\Controller;

use App\Form\ProfileSettingsFormType;
use App\Entity\Utilisateur;
use App\Service\FaceEmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private string $photosDirectory,
        private FaceEmbeddingService $faceEmbeddingService
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
            // VÃ©rifier que l'email n'est pas dÃ©jÃ  utilisÃ© par un autre utilisateur
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $existing = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $newEmail]);
                if ($existing) {
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
                    return $this->render('user/settings.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Vos paramÃ¨tres ont Ã©tÃ© enregistrÃ©s avec succÃ¨s.');
            return $this->redirectToRoute('app_settings');
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Enregistrement du visage (embedding face-api.js).
     * POST JSON: { "embedding": [128 floats] }
     */
    #[Route('/parametres/face-id/register', name: 'app_face_id_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function faceIdRegister(Request $request): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);
        $embedding = $data['embedding'] ?? null;
        if (!is_array($embedding) || count($embedding) < 128) {
            return new JsonResponse(['success' => false, 'message' => 'Embedding invalide (tableau de 128 nombres requis).'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $this->faceEmbeddingService->storeEmbedding($user, $embedding);
            $this->entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'Visage enregistré. Vous pouvez vous connecter avec la reconnaissance faciale.']);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

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

