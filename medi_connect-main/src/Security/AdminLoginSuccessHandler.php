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

        return new Response('', Response::HTTP_FOUND, [
            'Location' => $this->urlGenerator->generate('app_profile'),
        ]);
    }
}
