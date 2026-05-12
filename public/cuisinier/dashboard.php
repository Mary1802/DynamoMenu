<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cuisinier') {
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
    <title>Dashboard cuisinier - DynamoMenu</title>
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
            <p>Accédez aux commandes en cours et gérez la préparation en cuisine.</p>
            <div class="section-grid">
                <article class="feature-card">
                    <h3>Commandes en attente</h3>
                    <p>Voir les commandes qui doivent être préparées.</p>
                </article>
                <article class="feature-card">
                    <h3>État de la cuisine</h3>
                    <p>Suivez les plats en cours de préparation et terminés.</p>
                </article>
            </div>
        </div>
    </main>
</body>
</html>

