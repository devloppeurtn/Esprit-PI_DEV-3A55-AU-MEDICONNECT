<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Medecin;
use App\Entity\Organisateur;
use App\Entity\Participation;
use App\Entity\Patient;
use App\Entity\RoleParticipation;
use App\Entity\RoleUtilisateur;
use App\Entity\Secretaire;
use App\Entity\StatutCompte;
use App\Entity\Utilisateur;
use App\Form\ForgotPasswordFormType;
use App\Form\LoginFormType;
use App\Form\ResetPasswordFormType;
use App\Form\SignupFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private SluggerInterface $slugger,
        private string $photosDirectory
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $accountStatusError = $error instanceof AccountStatusException
                ? $error
                : (($previous = $error->getPrevious()) instanceof AccountStatusException ? $previous : null);

            if (!$accountStatusError) {
                $errorMessage = $error->getMessageKey();
                switch ($errorMessage) {
                    case 'Invalid credentials.':
                        $this->addFlash('error', 'Email ou mot de passe incorrect.');
                        break;
                    case 'User account is disabled.':
                        $this->addFlash('error', 'Votre compte est désactivé.');
                        break;
                    case 'User account is locked.':
                        $this->addFlash('error', 'Votre compte est verrouillé.');
                        break;
                    case 'User account has expired.':
                        $this->addFlash('error', 'Votre compte a expiré.');
                        break;
                    default:
                        $this->addFlash('error', 'Une erreur est survenue lors de la connexion. Veuillez réessayer.');
                }
            }
        }

        $form = $this->createForm(LoginFormType::class);

        return $this->render('auth/login.html.twig', [
            'form' => $form,
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/oubli-mot-de-passe', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $this->entityManager->flush();

                $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
                $emailMessage = (new TemplatedEmail())
                    ->from(Address::create($fromAddress))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - MediConnect')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'user' => $user,
                        'resetUrl' => $this->getResetUrl($token),
                        'expiresAt' => $user->getResetTokenExpiresAt(),
                    ]);
                try {
                    $this->mailer->send($emailMessage);
                } catch (TransportExceptionInterface $e) {
                    error_log('[MediConnect] Erreur envoi email reset password: ' . $e->getMessage());
                }
            }

            $this->addFlash('success', 'Si un compte existe avec cet email, vous recevrez un lien pour réinitialiser votre mot de passe. Vérifiez votre boîte de réception.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
            'resetToken' => $token,
        ]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verifier-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
            'verificationToken' => $token,
        ]);

        if (!$user || !$user->getVerificationTokenExpiresAt() || $user->getVerificationTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de vérification est invalide ou a expiré. Veuillez vous réinscrire ou demander un nouvel email.');
            return $this->redirectToRoute('app_login');
        }

        $user->setEmailVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/signup/{role?}', name: 'app_signup', defaults: ['role' => null])]
    public function signup(Request $request, ?string $role = null): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $roleEnum = null;
        if ($role) {
            try {
                $roleEnum = RoleUtilisateur::from($role);
            } catch (\ValueError $e) {
                $this->addFlash('error', 'Rôle invalide.');
                return $this->redirectToRoute('app_signup');
            }
        }

        $form = $this->createForm(SignupFormType::class, null, [
            'role' => $roleEnum,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $data['email']]);

            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('auth/signup.html.twig', [
                    'form' => $form,
                    'role' => $roleEnum,
                ]);
            }

            if ($data['password'] !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('auth/signup.html.twig', [
                    'form' => $form,
                    'role' => $roleEnum,
                ]);
            }

            $photoFile = $form->get('photo')->getData();
            $photoPath = null;
            if ($photoFile instanceof UploadedFile) {
                $photoPath = $this->handlePhotoUpload($photoFile);
                if (!$photoPath) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo. Veuillez réessayer.');
                    return $this->render('auth/signup.html.twig', [
                        'form' => $form,
                        'role' => $roleEnum,
                    ]);
                }
            }

            $user = $this->createUserByRole($roleEnum ?? RoleUtilisateur::PATIENT, $data);
            
            if ($photoPath) {
                $user->setPhoto($photoPath);
            }

            if ($user instanceof Admin) {
                $user->setStatut(StatutCompte::SUSPENDU);
            }

            $user->setEmailVerified(false);
            $verifyToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verifyToken);
            $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            try {
                $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
                $emailMessage = (new TemplatedEmail())
                    ->from(Address::create($fromAddress))
                    ->to($user->getEmail())
                    ->subject('Vérifiez votre compte MediConnect')
                    ->htmlTemplate('emails/verify_email.html.twig')
                    ->context([
                        'user' => $user,
                        'verifyUrl' => $this->getVerifyUrl($verifyToken),
                        'expiresAt' => $user->getVerificationTokenExpiresAt(),
                    ]);
                $this->mailer->send($emailMessage);
            } catch (TransportExceptionInterface $e) {
                error_log('[MediConnect] Erreur envoi email vérification: ' . $e->getMessage());
            }

            if ($user instanceof Admin) {
                $this->addFlash('success', 'Inscription réussie ! Un email de vérification vous a été envoyé. Cliquez sur le lien pour activer votre compte, puis attendez la validation par un administrateur.');
            } else {
                $this->addFlash('success', 'Inscription réussie ! Un email de vérification vous a été envoyé. Cliquez sur le lien pour activer votre compte et vous connecter.');
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/signup.html.twig', [
            'form' => $form,
            'role' => $roleEnum,
        ]);
    }

    #[Route('/api/login', name: 'app_api_login', methods: ['POST'])]
    public function apiLogin(Request $request, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        if ($this->getUser()) {
            return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_profile')]);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $username = $data['_username'] ?? $data['email'] ?? '';
        $password = $data['_password'] ?? $data['password'] ?? '';
        $csrfToken = $data['_csrf_token'] ?? '';

        if (empty($username) || empty($password)) {
            return new JsonResponse(['success' => false, 'error' => 'Email et mot de passe requis'], 400);
        }

        if (!$this->isCsrfTokenValid('authenticate', $csrfToken)) {
            return new JsonResponse(['success' => false, 'error' => 'Token CSRF invalide'], 403);
        }

        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $username]);
        if ($user) {
            if ($user->getStatut() === StatutCompte::SUSPENDU) {
                return new JsonResponse(['success' => false, 'error' => 'Votre compte est suspendu. Veuillez contacter un administrateur.'], 403);
            }
            if ($user->getStatut() === StatutCompte::BANNI) {
                return new JsonResponse(['success' => false, 'error' => 'Votre compte a été banni.'], 403);
            }
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Veuillez utiliser le formulaire de connexion standard',
            'useFormSubmit' => true
        ], 400);
    }

    #[Route('/api/signup', name: 'app_api_signup', methods: ['POST'])]
    public function apiSignup(Request $request): JsonResponse
    {
        if ($this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Vous êtes déjà connecté'], 400);
        }

        $isMultipart = $request->request->has('email') || $request->files->has('photo');
        $data = $isMultipart ? $request->request->all() : (json_decode($request->getContent(), true) ?? []);
        
        $email = isset($data['email']) ? trim($data['email']) : '';
        $nomComplet = isset($data['nomComplet']) ? trim($data['nomComplet']) : '';
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';
        $telephone = isset($data['telephone']) ? trim($data['telephone']) : '';
        $roleValue = $data['role'] ?? 'PATIENT';

        if (empty($email)) {
            return new JsonResponse(['success' => false, 'error' => 'L\'email est requis'], 400);
        }
        if (mb_strlen($email) > 180) {
            return new JsonResponse(['success' => false, 'error' => 'L\'email est trop long (maximum 180 caractères)'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Format d\'email invalide'], 400);
        }

        if (empty($nomComplet)) {
            return new JsonResponse(['success' => false, 'error' => 'Le nom complet est requis'], 400);
        }
        $nomCompletLength = mb_strlen($nomComplet);
        if ($nomCompletLength < 2) {
            return new JsonResponse(['success' => false, 'error' => 'Le nom complet doit contenir au moins 2 caractères'], 400);
        }
        if ($nomCompletLength > 255) {
            return new JsonResponse(['success' => false, 'error' => 'Le nom complet ne doit pas dépasser 255 caractères'], 400);
        }

        if (empty($password)) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe est requis'], 400);
        }
        $passwordLength = mb_strlen($password);
        if ($passwordLength < 6) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }
        if ($passwordLength > 4096) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe est trop long'], 400);
        }

        if (empty($confirmPassword)) {
            return new JsonResponse(['success' => false, 'error' => 'Veuillez confirmer votre mot de passe'], 400);
        }
        if ($password !== $confirmPassword) {
            return new JsonResponse(['success' => false, 'error' => 'Les mots de passe ne correspondent pas'], 400);
        }

        if (empty($telephone)) {
            return new JsonResponse(['success' => false, 'error' => 'Le numéro de téléphone est requis'], 400);
        }
        if (mb_strlen($telephone) > 20) {
            return new JsonResponse(['success' => false, 'error' => 'Le numéro de téléphone est trop long (maximum 20 caractères)'], 400);
        }

        if ($roleValue === 'MEDECIN') {
            $specialite = isset($data['specialite']) ? trim($data['specialite']) : '';
            if (empty($specialite)) {
                return new JsonResponse(['success' => false, 'error' => 'La spécialité est requise pour les médecins'], 400);
            }
            if (mb_strlen($specialite) > 255) {
                return new JsonResponse(['success' => false, 'error' => 'La spécialité est trop longue (maximum 255 caractères)'], 400);
            }
        }

        $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['success' => false, 'error' => 'Cet email est déjà utilisé'], 400);
        }

        try {
            $roleEnum = RoleUtilisateur::from($roleValue);
        } catch (\ValueError) {
            return new JsonResponse(['success' => false, 'error' => 'Rôle invalide'], 400);
        }

        $photoPath = null;
        if ($request->files->has('photo')) {
            $photoFile = $request->files->get('photo');
            if ($photoFile instanceof UploadedFile) {
                $photoPath = $this->handlePhotoUpload($photoFile);
                if (!$photoPath) {
                    return new JsonResponse(['success' => false, 'error' => 'Erreur lors de l\'upload de la photo'], 400);
                }
            }
        }

        $userData = array_merge($data, [
            'email' => $email,
            'nomComplet' => $nomComplet,
            'password' => $password,
            'telephone' => $telephone
        ]);
        
        if ($roleValue === 'MEDECIN' && isset($data['specialite'])) {
            $userData['specialite'] = trim($data['specialite']);
        }
        
        $user = $this->createUserByRole($roleEnum, $userData);

        if ($photoPath) {
            $user->setPhoto($photoPath);
        }

        if ($user instanceof Admin) {
            $user->setStatut(StatutCompte::SUSPENDU);
        }

        $user->setEmailVerified(false);
        $verifyToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verifyToken);
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
            $emailMessage = (new TemplatedEmail())
                ->from(Address::create($fromAddress))
                ->to($user->getEmail())
                ->subject('Vérifiez votre compte MediConnect')
                ->htmlTemplate('emails/verify_email.html.twig')
                ->context([
                    'user' => $user,
                    'verifyUrl' => $this->getVerifyUrl($verifyToken),
                    'expiresAt' => $user->getVerificationTokenExpiresAt(),
                ]);
            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            error_log('[MediConnect] Erreur envoi email vérification: ' . $e->getMessage());
        }

        $message = $user instanceof Admin
            ? 'Inscription réussie ! Un email de vérification vous a été envoyé. Cliquez sur le lien pour activer votre compte, puis attendez la validation par un administrateur.'
            : 'Inscription réussie ! Un email de vérification vous a été envoyé. Cliquez sur le lien pour activer votre compte et vous connecter.';

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'redirect' => $this->generateUrl('app_login')
        ]);
    }

    #[Route('/api/forgot-password', name: 'app_api_forgot_password', methods: ['POST'])]
    public function apiForgotPassword(Request $request): JsonResponse
    {
        if ($this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Vous êtes déjà connecté'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $email = isset($data['email']) ? trim($data['email']) : '';

        if (empty($email)) {
            return new JsonResponse(['success' => false, 'error' => 'L\'email est requis'], 400);
        }
        if (mb_strlen($email) > 180) {
            return new JsonResponse(['success' => false, 'error' => 'L\'email est trop long (maximum 180 caractères)'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Format d\'email invalide'], 400);
        }

        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
            $this->entityManager->flush();

            try {
                $fromAddress = $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'MediConnect <noreply@mediconnect.com>';
                $emailMessage = (new TemplatedEmail())
                    ->from(Address::create($fromAddress))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - MediConnect')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'user' => $user,
                        'resetUrl' => $this->getResetUrl($token),
                        'expiresAt' => $user->getResetTokenExpiresAt(),
                    ]);
                $this->mailer->send($emailMessage);
            } catch (TransportExceptionInterface $e) {
                error_log('[MediConnect] Erreur envoi email reset password: ' . $e->getMessage());
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Si un compte existe avec cet email, vous recevrez un lien pour réinitialiser votre mot de passe. Vérifiez votre boîte de réception.',
            'redirect' => $this->generateUrl('app_login')
        ]);
    }

    #[Route('/api/reset-password/{token}', name: 'app_api_reset_password', methods: ['POST'])]
    public function apiResetPassword(Request $request, string $token): JsonResponse
    {
        if ($this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Vous êtes déjà connecté'], 400);
        }

        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Ce lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.',
                'redirect' => $this->generateUrl('app_forgot_password')
            ], 400);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $password = $data['password'] ?? '';

        if (empty($password)) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe est requis'], 400);
        }
        $passwordLength = mb_strlen($password);
        if ($passwordLength < 6) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }
        if ($passwordLength > 4096) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe est trop long'], 400);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
            'redirect' => $this->generateUrl('app_login')
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide.');
    }

    private function getResetUrl(string $token): string
    {
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null;
        if ($appUrl) {
            $appUrl = rtrim($appUrl, '/');
            return $appUrl . $this->generateUrl('app_reset_password', ['token' => $token]);
        }
        return $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getVerifyUrl(string $token): string
    {
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null;
        if ($appUrl) {
            $appUrl = rtrim($appUrl, '/');
            return $appUrl . $this->generateUrl('app_verify_email', ['token' => $token]);
        }
        return $this->generateUrl('app_verify_email', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function createUserByRole(RoleUtilisateur $role, array $data): Utilisateur
    {
        $user = match ($role) {
            RoleUtilisateur::ADMIN => new Admin(),
            RoleUtilisateur::PATIENT => new Patient(),
            RoleUtilisateur::MEDECIN => new Medecin(),
            RoleUtilisateur::SECRETAIRE => new Secretaire(),
            RoleUtilisateur::PARTICIPATION => new Participation(),
            RoleUtilisateur::ORGANISATEUR => new Organisateur(),
        };

        $user->setEmail($data['email']);
        $user->setNomComplet($data['nomComplet']);

        if (isset($data['telephone']) && !empty($data['telephone'])) {
            if ($user instanceof Patient || $user instanceof Secretaire || $user instanceof Admin || $user instanceof Participation || $user instanceof Organisateur) {
                $user->setTelephone($data['telephone']);
            }
        }

        if ($user instanceof Patient) {
            if (isset($data['dateNaissance']) && !empty($data['dateNaissance'])) {
                $dateValue = $data['dateNaissance'];
                if (is_string($dateValue)) {
                    try {
                        $dateValue = new \DateTime($dateValue);
                    } catch (\Exception $e) {
                        error_log('[MediConnect] Format de date invalide pour dateNaissance: ' . $data['dateNaissance']);
                        $dateValue = null;
                    }
                }
                $user->setDateNaissance($dateValue);
            }
            if (isset($data['adresse'])) {
                $user->setAdresse($data['adresse']);
            }
        } elseif ($user instanceof Medecin) {
            if (isset($data['specialite'])) {
                $user->setSpecialite($data['specialite']);
            }
            if (isset($data['adresseCabinet'])) {
                $user->setAdresseCabinet($data['adresseCabinet']);
            }
            if (isset($data['numeroLicence'])) {
                $user->setNumeroLicence($data['numeroLicence']);
            }
        } elseif ($user instanceof Secretaire || $user instanceof Organisateur) {
            // Téléphone déjà géré ci-dessus
        } elseif ($user instanceof Participation) {
            if (isset($data['roleDansEvenement'])) {
                $user->setRoleDansEvenement($data['roleDansEvenement'] instanceof RoleParticipation
                    ? $data['roleDansEvenement']
                    : RoleParticipation::from($data['roleDansEvenement']));
            } else {
                $user->setRoleDansEvenement(RoleParticipation::PARTICIPANT);
            }
            if (isset($data['presenceConfirmee'])) {
                $user->setPresenceConfirmee((bool) $data['presenceConfirmee']);
            }
        }

        return $user;
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

        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        if (!is_dir($this->photosDirectory)) {
            mkdir($this->photosDirectory, 0755, true);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->toString();
        if ($safeFilename === '') {
            $safeFilename = 'photo';
        }

        $extension = in_array($clientExtension, $allowedExtensions, true) ? $clientExtension : 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        try {
            $file->move($this->photosDirectory, $newFilename);
            return 'uploads/photos/' . $newFilename;
        } catch (FileException $e) {
            error_log('[MediConnect] Erreur upload photo: ' . $e->getMessage());
            return null;
        }
    }
}
