<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Medecin;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
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
        private MailerInterface $mailer
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

        // Gérer les messages d'erreur spécifiques
        // Les comptes SUSPENDU/BANNI : le message est déjà ajouté par LoginFailureHandler.
        // Pour les autres erreurs, on ajoute le flash ici.
        if ($error) {
            $accountStatusError = $error instanceof AccountStatusException
                ? $error
                : (($previous = $error->getPrevious()) instanceof AccountStatusException ? $previous : null);

            // Ne pas ajouter de flash pour AccountStatusException (déjà fait par LoginFailureHandler)
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

            // Toujours afficher le même message pour éviter l'enumération d'emails
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
                    // Logger sans révéler si le compte existe (sécurité)
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

        // Valider le rôle
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

            // Vérifier si l'email existe déjà
            $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $data['email']]);

            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('auth/signup.html.twig', [
                    'form' => $form,
                    'role' => $roleEnum,
                ]);
            }

            // Vérifier la confirmation du mot de passe
            if ($data['password'] !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('auth/signup.html.twig', [
                    'form' => $form,
                    'role' => $roleEnum,
                ]);
            }

            // Créer l'utilisateur selon le rôle
            $user = $this->createUserByRole($roleEnum ?? RoleUtilisateur::PATIENT, $data);

            // Les admins inscrits restent SUSPENDU jusqu'à validation par un autre admin
            if ($user instanceof Admin) {
                $user->setStatut(StatutCompte::SUSPENDU);
            }

            // Vérification email : token + envoi
            $user->setEmailVerified(false);
            $verifyToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verifyToken);
            $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Envoyer l'email de vérification
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





    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé logout de votre firewall.');
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
        };

        $user->setEmail($data['email']);
        $user->setNomComplet($data['nomComplet']);

        // Remplir les champs spécifiques selon le rôle
        if ($user instanceof Patient) {
            if (isset($data['telephone'])) {
                $user->setTelephone($data['telephone']);
            }
            if (isset($data['dateNaissance'])) {
                $user->setDateNaissance($data['dateNaissance']);
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
        } elseif ($user instanceof Secretaire) {
            if (isset($data['telephone'])) {
                $user->setTelephone($data['telephone']);
            }
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
}
