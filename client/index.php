<?php
session_start();
$db_config = require __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/table_context.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    bootstrap_table_context($pdo);
} catch (PDOException $e) {
    die('Erreur de connexion');
}

$tableCtx = table_session();
$tableError = $_SESSION['table_error'] ?? null;
unset($_SESSION['table_error']);
$scanError = isset($_GET['err']) && $_GET['err'] === 'table';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Accueil</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold text-white" href="index.php">DynamoMenu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white" href="index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="menu.php">Menu</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white position-relative" href="panier.php">
                        Panier
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="#contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <a class="btn btn-primary ms-lg-4" href="#contact.php">Contact Now</a>
        </div>
    </header>

    <main class="container-fluid px-4 py-5 hero-section">
        <?php if ($tableError || $scanError): ?>
        <div class="alert alert-warning mb-4" role="alert">
            <?php echo htmlspecialchars($tableError ?: 'Veuillez scanner le QR code présent sur votre table pour commander.'); ?>
        </div>
        <?php elseif ($tableCtx): ?>
        <div class="alert alert-success mb-4 py-2" role="status">
            Vous êtes à la <strong><?php echo htmlspecialchars($tableCtx['label']); ?></strong> — bon appétit !
        </div>
        <?php endif; ?>
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase text-warning mb-3">Commandez. Mangez. Profitez ! </p>
                <h1 class="display-4 hero-title mb-4">Une nouvelle façon de commander :<br>rapide, pratique et totalement digitale</h1>
                <p class="hero-subtitle mb-4">Commandez votre repas en un clic et profitez-en dès maintenant.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary btn-lg" href="menu.php">Commander</a>
                    <a class="btn btn-outline-light btn-lg" href="menu.php">Voir menu</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="../assets/images/kombo/combo burger frites poulet.jpg" alt="Combo Burger Frites Poulet" class="img-fluid rounded-4 shadow-lg" style="height: 420px; object-fit: cover; width: 100%;">
            </div>
        </div>
    </main>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mettre à jour le badge du panier
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        // Cacher le badge si 0
                        if (data.count === 0) {
                            cartBadge.style.display = 'none';
                        } else {
                            cartBadge.style.display = 'block';
                        }
                    }
                });
        }
        
        // Mettre à jour au chargement
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        
        // Vérifier le panier toutes les 5 secondes
        setInterval(updateCartBadge, 5000);
    </script>
