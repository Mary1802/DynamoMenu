<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Security\Csrf;
use App\Security\SessionCookie;
use PDO;
use PDOException;

final class StaffAuthService
{
    public const SESSION_KEY = 'staff_user';
    public const SESSION_NAME = 'DM_STAFF';

    public function __construct(
        private readonly Config $config,
        private readonly SessionCookie $sessionCookie,
        private readonly Csrf $csrf,
        private readonly PDO $pdo
    ) {
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && session_name() !== self::SESSION_NAME) {
            session_write_close();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(self::SESSION_NAME);
            session_set_cookie_params($this->sessionCookie->params());
            session_start();
        }
    }

    /**
     * @param array<string, mixed> $employe
     */
    public function login(array $employe, string $role): void
    {
        $this->startSession();
        session_regenerate_id(true);
        $this->csrf->rotate();

        $_SESSION[self::SESSION_KEY] = [
            'user_id' => (int) $employe['id_employe'],
            'nom' => trim(($employe['nom_employe'] ?? '') . ' ' . ($employe['prenom_employe'] ?? '')),
            'email' => $employe['email_employe'],
            'role' => $role,
            'login_at' => time(),
        ];

        $_SESSION['user_id'] = (int) $employe['id_employe'];
        $_SESSION['nom'] = $_SESSION[self::SESSION_KEY]['nom'];
        $_SESSION['email'] = $employe['email_employe'];
        $_SESSION['role'] = $role;
    }

    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** @return array{user_id:int,nom:string,email:string,role:string,login_at:int}|null */
    public function user(): ?array
    {
        $this->startSession();
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($data) || empty($data['user_id']) || empty($data['role'])) {
            if (!empty($_SESSION['user_id']) && !empty($_SESSION['role']) && !empty($_SESSION['email'])) {
                $_SESSION[self::SESSION_KEY] = [
                    'user_id' => (int) $_SESSION['user_id'],
                    'nom' => (string) ($_SESSION['nom'] ?? 'Utilisateur'),
                    'email' => (string) $_SESSION['email'],
                    'role' => (string) $_SESSION['role'],
                    'login_at' => (int) ($_SESSION[self::SESSION_KEY]['login_at'] ?? time()),
                ];
                $data = $_SESSION[self::SESSION_KEY];
            } else {
                return null;
            }
        }

        if (time() - (int) ($data['login_at'] ?? 0) > $this->config->staffSessionLifetime()) {
            $this->logout();

            return null;
        }

        return $data;
    }

    /**
     * @param list<string> $allowedRoles
     * @return array{user_id:int,nom:string,email:string,role:string,login_at:int}
     */
    public function require(array $allowedRoles, string $loginRedirect = '../login.php'): array
    {
        $user = $this->user();
        if ($user === null) {
            header('Location: ' . $loginRedirect);
            exit;
        }

        if (!in_array($user['role'], $allowedRoles, true)) {
            $this->logout();
            header('Location: ' . $loginRedirect . '?err=role');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->csrf->verifyOrAbort();
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id_employe, role FROM employe WHERE id_employe = ? AND email_employe = ?');
            $stmt->execute([$user['user_id'], $user['email']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['role'] !== $user['role']) {
                $this->logout();
                header('Location: ' . $loginRedirect . '?err=session');
                exit;
            }
        } catch (PDOException $e) {
            $this->logout();
            header('Location: ' . $loginRedirect . '?err=db');
            exit;
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return $user;
    }

    public function dashboardUrl(string $role): string
    {
        $urls = [
            'admin' => 'admin/dashboard.php',
            'cuisinier' => 'cuisine/dashboard.php',
            'caissier' => 'caissier/paiement.php',
        ];

        return $urls[$role] ?? 'login.php';
    }

    public function roleLabel(string $role): string
    {
        $labels = [
            'admin' => 'Administrateur',
            'cuisinier' => 'Cuisinier',
            'caissier' => 'Caissier',
        ];

        return $labels[$role] ?? $role;
    }
}
