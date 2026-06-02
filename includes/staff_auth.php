<?php

/**
 * Sessions employés (admin, cuisinier, caissier).
 */

const STAFF_SESSION_KEY = 'staff_user';
const STAFF_SESSION_LIFETIME = 28800; // 8 h

function staff_session_cookie_path(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }

    $appFile = dirname(__DIR__) . '/config/app.php';
    if (is_file($appFile)) {
        $app = require $appFile;
        $basePath = parse_url($app['base_url'] ?? '', PHP_URL_PATH);
        if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
            $path = rtrim($basePath, '/') . '/';

            return $path;
        }
    }

    $path = '/';

    return $path;
}

function staff_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => staff_session_cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function staff_login(array $employe, string $role): void
{
    staff_session_start();
    session_regenerate_id(true);

    $_SESSION[STAFF_SESSION_KEY] = [
        'user_id' => (int) $employe['id_employe'],
        'nom' => trim(($employe['nom_employe'] ?? '') . ' ' . ($employe['prenom_employe'] ?? '')),
        'email' => $employe['email_employe'],
        'role' => $role,
        'login_at' => time(),
    ];

    // Compatibilité avec l'existant
    $_SESSION['user_id'] = (int) $employe['id_employe'];
    $_SESSION['nom'] = $_SESSION[STAFF_SESSION_KEY]['nom'];
    $_SESSION['email'] = $employe['email_employe'];
    $_SESSION['role'] = $role;
}

function staff_logout(): void
{
    staff_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** @return array{user_id:int,nom:string,email:string,role:string,login_at:int}|null */
function staff_user(): ?array
{
    staff_session_start();
    $data = $_SESSION[STAFF_SESSION_KEY] ?? null;
    if (!is_array($data) || empty($data['user_id']) || empty($data['role'])) {
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['role']) && !empty($_SESSION['email'])) {
            $_SESSION[STAFF_SESSION_KEY] = [
                'user_id' => (int) $_SESSION['user_id'],
                'nom' => (string) ($_SESSION['nom'] ?? 'Utilisateur'),
                'email' => (string) $_SESSION['email'],
                'role' => (string) $_SESSION['role'],
                'login_at' => time(),
            ];
            $data = $_SESSION[STAFF_SESSION_KEY];
        } else {
            return null;
        }
    }

    if (time() - (int) ($data['login_at'] ?? 0) > STAFF_SESSION_LIFETIME) {
        staff_logout();

        return null;
    }

    return $data;
}

/**
 * @param list<string> $allowedRoles
 */
function staff_require(array $allowedRoles, string $loginRedirect = '../login.php'): array
{
    $user = staff_user();
    if ($user === null) {
        header('Location: ' . $loginRedirect);
        exit;
    }

    if (!in_array($user['role'], $allowedRoles, true)) {
        staff_logout();
        header('Location: ' . $loginRedirect . '?err=role');
        exit;
    }

    $db_config = require dirname(__DIR__) . '/config/db.php';
    try {
        $pdo = new PDO(
            'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
            $db_config['user'],
            $db_config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare('SELECT id_employe, role FROM employe WHERE id_employe = ? AND email_employe = ?');
        $stmt->execute([$user['user_id'], $user['email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['role'] !== $user['role']) {
            staff_logout();
            header('Location: ' . $loginRedirect . '?err=session');
            exit;
        }
    } catch (PDOException $e) {
        staff_logout();
        header('Location: ' . $loginRedirect . '?err=db');
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    return $user;
}

function staff_dashboard_url(string $role): string
{
    $urls = [
        'admin' => 'admin/dashboard.php',
        'cuisinier' => 'cuisine/dashboard.php',
        'caissier' => 'caissier/paiement.php',
    ];

    return $urls[$role] ?? 'login.php';
}

function staff_role_label(string $role): string
{
    $labels = [
        'admin' => 'Administrateur',
        'cuisinier' => 'Cuisinier',
        'caissier' => 'Caissier',
    ];

    return $labels[$role] ?? $role;
}
