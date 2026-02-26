<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepository
{
    public function __construct(private readonly UtilisateurRepository $utilisateurRepository)
    {
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $user = $this->utilisateurRepository->findOneBy(['email' => $username]);

        if (!$user instanceof Utilisateur) {
            return null;
        }

        return $this->createUserEntityFromUtilisateur($user);
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        if ($userHandle === '') {
            return null;
        }

        $id = ctype_digit($userHandle) ? (int) $userHandle : null;
        if ($id === null) {
            return null;
        }

        $user = $this->utilisateurRepository->find($id);
        if (!$user instanceof Utilisateur) {
            return null;
        }

        return $this->createUserEntityFromUtilisateur($user);
    }

    public function generateNextUserEntityId(): string
    {
        // Dans notre cas, l'ID WebAuthn est simplement l'ID numérique de l'utilisateur en base
        // Cette méthode n'est pas utilisée car les comptes sont créés via le flux d'inscription existant.
        return (string) random_int(1, PHP_INT_MAX);
    }

    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity): void
    {
        // Aucune action : les utilisateurs sont gérés par Doctrine via l'entité Utilisateur.
    }

    private function createUserEntityFromUtilisateur(Utilisateur $user): PublicKeyCredentialUserEntity
    {
        $userId = (string) $user->getId();
        $name = $user->getNomComplet() ?? $user->getEmail() ?? $userId;

        return new PublicKeyCredentialUserEntity(
            $user->getEmail() ?? $userId,
            $userId,
            $name
        );
    }
}

