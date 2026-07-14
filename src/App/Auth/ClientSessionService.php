<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Security\Csrf;
use App\Security\SessionCookie;

final class ClientSessionService
{
    public const SESSION_NAME = 'DM_CLIENT';

    public function __construct(
        private readonly Config $config,
        private readonly SessionCookie $sessionCookie,
        private readonly Csrf $csrf
    ) {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && session_name() !== self::SESSION_NAME) {
            session_write_close();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(self::SESSION_NAME);
            session_set_cookie_params($this->sessionCookie->params());
            session_start();
        }

        $this->enforceTimeout();
    }

    public function enforceTimeout(): void
    {
        $lifetime = $this->config->clientSessionLifetime();
        $last = (int) ($_SESSION['_client_last_activity'] ?? 0);

        if ($last > 0 && time() - $last > $lifetime) {
            $tableKeys = [
                'num_table',
                'table_code',
                'table_label',
                'client_nom',
                'client_prenom',
                'client_email',
                'client_telephone',
                'id_client',
                'client_identite_locked',
                'order_access',
                'suivi_commande_id',
                '_client_started_at',
            ];
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

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    public function verifyPostCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->csrf->verifyOrAbort();
        }
    }
}
