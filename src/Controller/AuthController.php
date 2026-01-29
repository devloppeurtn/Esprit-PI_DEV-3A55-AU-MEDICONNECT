<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\RoleUtilisateur;
use App\Entity\Secretaire;
use App\Entity\Utilisateur;
use App\Form\LoginFormType;
use App\Form\SignupFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
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
        if ($error) {
            $errorMessage = $error->getMessageKey();
            
            // Traduire les messages d'erreur
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

        $form = $this->createForm(LoginFormType::class);

        return $this->render('auth/login.html.twig', [
            'form' => $form,
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
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

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
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

    private function createUserByRole(RoleUtilisateur $role, array $data): Utilisateur
    {
        $user = match ($role) {
            RoleUtilisateur::ADMIN => new Admin(),
            RoleUtilisateur::PATIENT => new Patient(),
            RoleUtilisateur::MEDECIN => new Medecin(),
            RoleUtilisateur::SECRETAIRE => new Secretaire(),
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
        }

        return $user;
    }
}
