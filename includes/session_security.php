<?php

/**
 * Sécurité sessions, CSRF et mots de passe employés.
 */

require_once __DIR__ . '/app_url.php';

const CSRF_SESSION_KEY = '_csrf_token';

function password_is_hashed(string $stored): bool
{
    return preg_match('/^\$(2[ay]|argon2)/', $stored) === 1;
}

function app_secret(): string{
    $config = app_config();
    $secret = (string) ($config['session_secret'] ?? '');
    if ($secret === '' || $secret === 'change-me-in-production') {
        $secret = hash('sha256', dirname(__DIR__) . '|' . php_uname('n'));
    }

    return $secret;
}

function session_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
}

function app_cookie_path(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }

    $basePath = parse_url((string) (app_config()['base_url'] ?? ''), PHP_URL_PATH);
    if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
        $path = rtrim($basePath, '/') . '/';

        return $path;
    }

    $path = '/';

    return $path;
}

/** @return array{lifetime:int,path:string,httponly:bool,samesite:string,secure:bool} */
function session_cookie_params(): array
{
    return [
        'lifetime' => 0,
        'path' => app_cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => session_is_https(),
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION[CSRF_SESSION_KEY];
}

function csrf_rotate(): void
{
    $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
}

function csrf_verify(?string $token = null): bool
{
    $token = $token ?? ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = (string) ($_SESSION[CSRF_SESSION_KEY] ?? '');

    return $expected !== '' && hash_equals($expected, (string) $token);
}

function csrf_verify_or_abort(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        exit('Session expirée ou requête invalide. Rechargez la page et réessayez.');
    }
}

function csrf_field(): void
{
    echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_meta_tag(): void
{
    echo '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function password_verify_employe(string $plain, string $stored): bool
{
    $stored = trim($stored);
    if ($stored === '') {
        return false;
    }

    if (password_is_hashed($stored)) {
        return password_verify($plain, $stored);
    }

    return hash_equals($stored, $plain);
}

function password_hash_employe(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function password_employe_needs_rehash(string $stored): bool
{
    $stored = trim($stored);
    if ($stored === '' || !password_is_hashed($stored)) {
        return true;
    }

    return password_needs_rehash($stored, PASSWORD_DEFAULT);
}

function client_order_grant_access(int $numCommande): void
{
    if ($numCommande <= 0) {
        return;
    }

    if (!isset($_SESSION['order_access']) || !is_array($_SESSION['order_access'])) {
        $_SESSION['order_access'] = [];
    }

    if (!in_array($numCommande, $_SESSION['order_access'], true)) {
        $_SESSION['order_access'][] = $numCommande;
    }

    if (count($_SESSION['order_access']) > 20) {
        $_SESSION['order_access'] = array_slice($_SESSION['order_access'], -20);
    }
}

function client_order_token(int $numCommande): string
{
    return hash_hmac('sha256', (string) $numCommande, app_secret());
}

function client_order_token_verify(int $numCommande, string $token): bool
{
    return hash_equals(client_order_token($numCommande), $token);
}

/**
 * @param array<string, mixed> $commande
 */
function client_can_access_order(array $commande, ?string $token = null): bool
{
    $num = (int) ($commande['num_commande'] ?? 0);
    if ($num <= 0) {
        return false;
    }

    if (!empty($_SESSION['suivi_commande_id']) && (int) $_SESSION['suivi_commande_id'] === $num) {
        return true;
    }

    if (!empty($_SESSION['order_access']) && is_array($_SESSION['order_access']) && in_array($num, $_SESSION['order_access'], true)) {
        return true;
    }

    if (!function_exists('table_session')) {
        require_once __DIR__ . '/table_context.php';
    }

    $ctx = table_session();
    if ($ctx !== null && (int) ($commande['num_table'] ?? 0) === (int) $ctx['num_table']) {
        return true;
    }

    if ($token !== null && $token !== '' && client_order_token_verify($num, $token)) {
        return true;
    }

    return false;
}

function client_require_order_access(array $commande, ?string $token = null): void
{
    if (!client_can_access_order($commande, $token)) {
        http_response_code(403);
        header('Location: index.php?err=access');
        exit;
    }
}
