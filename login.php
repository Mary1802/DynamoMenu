<?php

require_once __DIR__ . '/includes/staff_auth.php';
require_once __DIR__ . '/includes/employe_passwords.php';

staff_session_start();

$db_config = require 'config/db.php';

try {
    $pdoBoot = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    employe_upgrade_passwords($pdoBoot);
} catch (PDOException $e) {
    // Connexion BDD indisponible — message affiché à la soumission
}

$error = '';
$success = isset($_GET['logout']) ? 'Vous êtes déconnecté.' : '';
if (isset($_GET['err'])) {
    $error = match ($_GET['err']) {
        'role' => 'Accès refusé pour ce rôle.',
        'session' => 'Session expirée ou compte modifié. Reconnectez-vous.',
        'db' => 'Impossible de vérifier la session.',
        default => 'Veuillez vous connecter.',
    };
}

$current = staff_user();
if ($current !== null) {
    header('Location: ' . staff_dashboard_url($current['role']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Session expirée. Rechargez la page et réessayez.';
    } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($email === '' || $password === '' || $role === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
                $db_config['user'],
                $db_config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            employe_upgrade_passwords($pdo);

            $stmt = $pdo->prepare('SELECT id_employe, nom_employe, prenom_employe, email_employe, mot_de_passe, role FROM employe WHERE email_employe = ? AND role = ?');
            $stmt->execute([$email, $role]);
            $employe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employe && password_verify_employe($password, (string) $employe['mot_de_passe']) && $employe['role'] === $role) {
                if (password_employe_needs_rehash((string) $employe['mot_de_passe'])) {
                    $hash = password_hash_employe($password);
                    $pdo->prepare('UPDATE employe SET mot_de_passe = ? WHERE id_employe = ?')->execute([$hash, $employe['id_employe']]);
                }
                staff_login($employe, $role);
                header('Location: ' . staff_dashboard_url($role));
                exit;
            }

            $error = 'Email, mot de passe ou rôle incorrect.';
        } catch (PDOException $e) {
            $error = 'Erreur de connexion. Vérifiez la configuration de la base de données.';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DynamoMenu</title>
    <script>try{var _t=localStorage.getItem('dm_dashboard_theme');if(_t==='light')document.documentElement.classList.add('theme-light');}catch(e){}</script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css?v=3">
    <?php csrf_meta_tag(); ?>
</head>
<body class="login-page">
    <div class="login-theme-bar">
        <div class="theme-switcher" role="group" aria-label="Thème d'affichage">
            <button type="button" class="theme-switch-btn" data-theme-set="dark" aria-pressed="true">
                <i class="bi bi-moon-stars" aria-hidden="true"></i>
                <span class="theme-switch-label">Sombre</span>
            </button>
            <button type="button" class="theme-switch-btn" data-theme-set="light" aria-pressed="false">
                <i class="bi bi-sun" aria-hidden="true"></i>
                <span class="theme-switch-label">Clair</span>
            </button>
        </div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <h1>DynamoMenu</h1>
            <p>Connexion Employé</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Rôle</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">-- Sélectionner un rôle --</option>
                    <option value="cuisinier"<?php echo (($_POST['role'] ?? '') === 'cuisinier') ? ' selected' : ''; ?>>Cuisinier</option>
                    <option value="caissier"<?php echo (($_POST['role'] ?? '') === 'caissier') ? ' selected' : ''; ?>>Caissier</option>
                    <option value="admin"<?php echo (($_POST['role'] ?? '') === 'admin') ? ' selected' : ''; ?>>Administrateur</option>
                </select>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>
        </form>

        <div class="back-link">
            <a href="client/index.php">← Retour à l'accueil</a>
        </div>
        <p class="login-hint text-secondary small mt-3 mb-0">
            Identifiants fournis par l'administrateur de l'établissement.
        </p>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js?v=1"></script>
    <script src="assets/js/csrf.js?v=1"></script>
</body>
</html>
