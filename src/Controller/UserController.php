<?php

namespace App\Controller;

use App\Form\ProfileSettingsFormType;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
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
}
