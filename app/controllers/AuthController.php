<?php

require_once __DIR__ . '/../models/Utilisateur.php';

class AuthController
{
    public static function attempt(string $username, string $password): ?array
    {
        return Utilisateur::authenticate($username, $password);
    }
}

