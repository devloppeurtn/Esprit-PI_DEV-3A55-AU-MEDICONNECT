<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

/**
 * Gestionnaire d'échec de connexion qui affiche le bon message
 * pour les comptes suspendus ou bannis (au lieu de "Email ou mot de passe incorrect").
 */
class LoginFailureHandler extends DefaultAuthenticationFailureHandler
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Récupérer l'exception de statut de compte (SUSPENDU/BANNI)
        // Symfony la convertit en BadCredentialsException, elle est dans getPrevious()
        $accountStatusException = $exception instanceof AccountStatusException
            ? $exception
            : (($prev = $exception->getPrevious()) instanceof AccountStatusException ? $prev : null);

        if ($accountStatusException) {
            $request->getSession()->getFlashBag()->add('error', $accountStatusException->getMessage());
            // Stocker l'exception d'origine pour que getLastAuthenticationError() la retourne
            $exception = $accountStatusException;
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
