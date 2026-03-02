<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AdminLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if ($user instanceof Utilisateur && \in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new Response('', Response::HTTP_FOUND, [
                'Location' => $this->urlGenerator->generate('app_admin_dashboard'),
            ]);
        }

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        if ($user instanceof Utilisateur && \in_array('ROLE_SECRETAIRE', $user->getRoles(), true)) {
            return new Response('', Response::HTTP_FOUND, [
                'Location' => $this->urlGenerator->generate('app_secretaire_index'),
            ]);
        }

        if ($user instanceof Utilisateur && \in_array('ROLE_PATIENT', $user->getRoles(), true)) {
            return new Response('', Response::HTTP_FOUND, [
                'Location' => $this->urlGenerator->generate('app_patient_index'),
            ]);
        }

        if ($user instanceof Utilisateur && \in_array('ROLE_MEDECIN', $user->getRoles(), true)) {
            return new Response('', Response::HTTP_FOUND, [
                'Location' => $this->urlGenerator->generate('app_medecin_index'),
            ]);
        }

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return new Response('', Response::HTTP_FOUND, [
            'Location' => $this->urlGenerator->generate('app_profile'),
        ]);
    }
}
