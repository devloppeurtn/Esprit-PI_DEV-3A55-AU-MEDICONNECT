<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\StatutCompte;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        if ($user->getStatut() === StatutCompte::SUSPENDU) {
            throw new CustomUserMessageAccountStatusException('Votre compte est en attente de validation par un administrateur.');
        }

        if ($user->getStatut() === StatutCompte::BANNI) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été banni.');
        }

        if (!$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('Veuillez vérifier votre adresse email avant de vous connecter. Consultez votre boîte de réception.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
