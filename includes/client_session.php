<?php

/**
 * Sessions client (panier, table QR) — séparées du staff.
 */

require_once __DIR__ . '/session_security.php';

const CLIENT_SESSION_NAME = 'DM_CLIENT';
const CLIENT_SESSION_DEFAULT_LIFETIME = 14400;

function client_session_lifetime(): int
{
    $config = app_config();

    return (int) ($config['client_session_lifetime'] ?? CLIENT_SESSION_DEFAULT_LIFETIME);
}

function client_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== CLIENT_SESSION_NAME) {
        session_write_close();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name(CLIENT_SESSION_NAME);
        session_set_cookie_params(session_cookie_params());
        session_start();
    }

    client_session_enforce_timeout();
}

function client_session_enforce_timeout(): void
{
    $lifetime = client_session_lifetime();
    $last = (int) ($_SESSION['_client_last_activity'] ?? 0);

    if ($last > 0 && time() - $last > $lifetime) {
        $tableKeys = ['num_table', 'table_code', 'table_label'];
        $preserved = [];
        foreach ($tableKeys as $key) {
            if (isset($_SESSION[$key])) {
                $preserved[$key] = $_SESSION[$key];
            }
        }

        $_SESSION = $preserved;
    }

    if (empty($_SESSION['_client_started_at'])) {
        $_SESSION['_client_started_at'] = time();
    }

    $_SESSION['_client_last_activity'] = time();
}

function client_logout(): void
{
    client_session_start();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

function client_verify_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify_or_abort();
    }
}
