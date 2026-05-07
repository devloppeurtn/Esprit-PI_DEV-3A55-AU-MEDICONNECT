<?php

namespace App\Controller;

use App\Form\ProfileSettingsFormType;
use App\Entity\Utilisateur;
use App\Service\AwsFaceIdService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
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
        private AwsFaceIdService $faceIdService,
        private GoogleAuthenticatorInterface $googleAuthenticator
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

        $twoFactorQr = null;
        $twoFactorSecret = null;
        $twoFactorJustEnabled = (bool) $request->query->get('twoFactorEnabled', false);
        $twoFactorJustDisabled = (bool) $request->query->get('twoFactorDisabled', false);

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $session = $request->getSession();
            $pendingSecret = $session->get('two_factor_pending_secret');
            if (!$pendingSecret) {
                $pendingSecret = $this->googleAuthenticator->generateSecret();
                $session->set('two_factor_pending_secret', $pendingSecret);
            }

            $user->setGoogleAuthenticatorSecret($pendingSecret);
            $qrContent = $this->googleAuthenticator->getQRContent($user);
            $writer = extension_loaded('gd') ? new PngWriter() : new SvgWriter();
            $qrResult = (new Builder(
                writer: $writer,
                data: $qrContent,
                size: 220,
                margin: 10
            ))->build();
            $twoFactorQr = $qrResult->getDataUri();
            $twoFactorSecret = $pendingSecret;
            $user->setGoogleAuthenticatorSecret(null);
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form,
            'user' => $user,
            'twoFactorQr' => $twoFactorQr,
            'twoFactorSecret' => $twoFactorSecret,
            'twoFactorJustEnabled' => $twoFactorJustEnabled,
            'twoFactorJustDisabled' => $twoFactorJustDisabled,
        ]);
    }

    #[Route('/parametres/2fa/enable', name: 'app_2fa_enable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enableTwoFactor(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('2fa_enable', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_settings');
        }

        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'La double authentification est dÃ©jÃ  activÃ©e.');
            return $this->redirectToRoute('app_settings');
        }

        $pendingSecret = $request->getSession()->get('two_factor_pending_secret');
        if (!$pendingSecret) {
            $this->addFlash('error', 'Session expirÃ©e. Veuillez rÃ©essayer.');
            return $this->redirectToRoute('app_settings');
        }

        $code = trim((string) $request->request->get('auth_code'));
        if ($code === '') {
            $this->addFlash('error', 'Code requis.');
            return $this->redirectToRoute('app_settings');
        }

        $user->setGoogleAuthenticatorSecret($pendingSecret);
        if (!$this->googleAuthenticator->checkCode($user, $code)) {
            $user->setGoogleAuthenticatorSecret(null);
            $this->addFlash('error', 'Code invalide.');
            return $this->redirectToRoute('app_settings');
        }

        $this->entityManager->flush();
        $request->getSession()->remove('two_factor_pending_secret');
        $this->addFlash('success', 'Double authentification activée.');
        return $this->redirectToRoute('app_settings', ['twoFactorEnabled' => 1]);
    }

    #[Route('/parametres/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disableTwoFactor(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('2fa_disable', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_settings');
        }

        $user->setGoogleAuthenticatorSecret(null);
        $this->entityManager->flush();
        $request->getSession()->remove('two_factor_pending_secret');
        $this->addFlash('success', 'Double authentification désactivée.');
        return $this->redirectToRoute('app_settings', ['twoFactorDisabled' => 1]);
    }

    /**
     * Enregistrement du visage via AWS Rekognition.
     * POST JSON: { "image_base64": "..." }
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
        $imageBase64 = $data['image_base64'] ?? null;
        if (!is_string($imageBase64) || $imageBase64 === '') {
            return new JsonResponse(['success' => false, 'message' => 'Image base64 requise.'], Response::HTTP_BAD_REQUEST);
        }

        // Mode démo : ne plus appeler AWS Rekognition, mais simplement stocker l'image localement.
        try {
            $payload = $imageBase64;
            if (str_contains($payload, 'base64,')) {
                $payload = substr($payload, strpos($payload, 'base64,') + 7);
            }

            $decoded = base64_decode($payload, true);
            if ($decoded === false || $decoded === '') {
                return new JsonResponse(['success' => false, 'message' => 'Image base64 invalide.'], 400);
            }

            // Dossier de stockage pour les visages (réutilise le répertoire photos)
            $dir = rtrim($this->photosDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'faceid';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'faceid-user-' . $user->getId() . '-' . uniqid() . '.jpg';
            $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($fullPath, $decoded);

            // Marquer la biométrie comme activée (stockage local uniquement en mode démo).
            $user->setBiometricEnabled(true);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Votre visage a été enregistré avec succès. Vous pourrez utiliser la connexion par visage depuis l\'écran de connexion.',
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du visage.'], 400);
        }
    }

    #[Route('/parametres/face-id/unregister', name: 'app_face_id_unregister', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function faceIdUnregister(Request $request): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        // Mode démo : ne plus appeler AWS, simplement désactiver le flag biométrie.
        $user->setBiometricEnabled(false);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Face ID désactivé (mode démo).']);
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


