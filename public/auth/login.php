<?php
session_start();

if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'cuisinier') {
        header('Location: ../cuisinier/dashboard.php');
        exit;
    }
    if ($role === 'caissier') {
        header('Location: ../caissier/dashboard.php');
        exit;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    require_once __DIR__ . '/../../app/controllers/AuthController.php';

    $user = AuthController::attempt($username, $password);

    if ($user) {
        $_SESSION['user'] = $user;
        if ($user['role'] === 'cuisinier') {
            header('Location: ../cuisinier/dashboard.php');
            exit;
        }
        if ($user['role'] === 'caissier') {
            header('Location: ../caissier/dashboard.php');
            exit;
        }
    }

    $message = 'Identifiants incorrects. Veuillez réessayer.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <h1>Connexion</h1>
            <p>Accédez à votre dashboard cuisinier ou caissier.</p>
            <?php if ($message): ?>
                <div class="form-error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <label>Utilisateur</label>
                <input type="text" name="username" required>
                <label>Mot de passe</label>
                <input type="password" name="password" required>
                <button class="btn btn-primary" type="submit">Se connecter</button>
            </form>
            <a class="auth-link" href="../index.php">Retour accueil</a>
        </div>
    </main>
</body>
</html>
