<?php

declare(strict_types=1);

namespace App\Security;

final class PasswordHasher
{
    public function isHashed(string $stored): bool
    {
        return preg_match('/^\$(2[ay]|argon2)/', $stored) === 1;
    }

    public function verify(string $plain, string $stored): bool
    {
        $stored = trim($stored);
        if ($stored === '') {
            return false;
        }

        if ($this->isHashed($stored)) {
            return password_verify($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function needsRehash(string $stored): bool
    {
        $stored = trim($stored);
        if ($stored === '' || !$this->isHashed($stored)) {
            return true;
        }

        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    public function isValidLength(string $password, int $min = 6): bool
    {
        return strlen($password) >= $min;
    }
}
