<?php

declare(strict_types=1);

namespace App\Security;

final class Csrf
{
    public const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function rotate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }

    public function verify(?string $token = null): bool
    {
        $token = $token ?? ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = (string) ($_SESSION[self::SESSION_KEY] ?? '');

        return $expected !== '' && hash_equals($expected, (string) $token);
    }

    public function verifyOrAbort(): void
    {
        if (!$this->verify()) {
            http_response_code(403);
            exit('Session expirée ou requête invalide. Rechargez la page et réessayez.');
        }
    }

    public function field(): void
    {
        echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public function metaTag(): void
    {
        echo '<meta name="csrf-token" content="' . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
