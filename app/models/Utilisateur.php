<?php

class Utilisateur
{
    private static array $users = [
        ['username' => 'cuisinier', 'password' => 'cuisine123', 'role' => 'cuisinier', 'displayName' => 'Chef de cuisine'],
        ['username' => 'caissier', 'password' => 'caisse123', 'role' => 'caissier', 'displayName' => 'Caissier'],
    ];

    public static function authenticate(string $username, string $password): ?array
    {
        foreach (self::$users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                return $user;
            }
        }
        return null;
    }
}

