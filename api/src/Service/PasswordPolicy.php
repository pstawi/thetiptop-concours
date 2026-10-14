<?php

namespace App\Service;

use App\Http\ApiException;

final class PasswordPolicy
{
    public function assertValid(string $password): void
    {
        if (\strlen($password) < 8) {
            throw ApiException::validation('Le mot de passe doit contenir au moins 8 caractères.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw ApiException::validation('Le mot de passe doit contenir au moins une majuscule.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw ApiException::validation('Le mot de passe doit contenir au moins une minuscule.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw ApiException::validation('Le mot de passe doit contenir au moins un chiffre.');
        }
    }
}
