<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'caissier') {
    header('Location: ../auth/login.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard caissier - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="dashboard.php">DynamoMenu</a>
            <a class="btn btn-secondary" href="../auth/logout.php">Déconnexion</a>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <h1>Bienvenue, <?= htmlspecialchars($user['displayName']) ?></h1>
            <p>Accédez à la caisse, préparez les additions et suivez les paiements.</p>
            <div class="section-grid">
                <article class="feature-card">
                    <h3>Suivi des paiements</h3>
                    <p>Consulter les commandes à facturer et les règlements en cours.</p>
                </article>
                <article class="feature-card">
                    <h3>Validation rapide</h3>
                    <p>Émettre l’addition rapidement pour améliorer le service.</p>
                </article>
            </div>
        </div>
    </main>
</body>
</html>

