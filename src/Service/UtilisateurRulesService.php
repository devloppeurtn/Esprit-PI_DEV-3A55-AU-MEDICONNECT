<?php

namespace App\Service;

final class UtilisateurRulesService
{
    public function isPasswordLengthValid(string $password, int $minLength = 8): bool
    {
        return mb_strlen($password) >= $minLength;
    }

    /**
     * @param array<int, string> $existingEmails
     */
    public function isEmailUnique(string $email, array $existingEmails): bool
    {
        $normalized = mb_strtolower(trim($email));
        foreach ($existingEmails as $existing) {
            if ($normalized === mb_strtolower(trim($existing))) {
                return false;
            }
        }

        return true;
    }
}
