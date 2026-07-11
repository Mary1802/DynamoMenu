<?php

require_once __DIR__ . '/bootstrap/app.php';

use App\Http\ClientPage;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
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
    <link rel="stylesheet" href="assets/css/login.css?v=11">
    <link rel="stylesheet" href="assets/css/theme-toggle.css?v=1">
    <?php ClientPage::csrfMetaTag(); ?>
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="login-theme-bar">
            <?php \App\View\Staff\DashboardLayoutView::themeToggle(); ?>
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
            <?php ClientPage::csrfField(); ?>
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
                    <option value="manager"<?php echo $postRole === 'manager' ? ' selected' : ''; ?>>Manager</option>
                    <option value="cuisinier"<?php echo $postRole === 'cuisinier' ? ' selected' : ''; ?>>Cuisinier</option>
                    <option value="caissier"<?php echo $postRole === 'caissier' ? ' selected' : ''; ?>>Caissier</option>
                    <option value="admin"<?php echo $postRole === 'admin' ? ' selected' : ''; ?>>Administrateur</option>
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
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js?v=3"></script>
    <script src="assets/js/csrf.js?v=1"></script>
</body>
</html>
