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
    table_redirect_after_scan('index.php');
} catch (PDOException $e) {
    die('Erreur de connexion');
}

$tableCtx = table_session();
$tableError = $_SESSION['table_error'] ?? null;
unset($_SESSION['table_error']);
$scanError = isset($_GET['err']) && $_GET['err'] === 'table';
$menuUrl = table_link('menu.php');
$panierUrl = table_link('panier.php');
$appConfig = require __DIR__ . '/../config/app.php';
$contacts = is_array($appConfig['contacts'] ?? null) ? $appConfig['contacts'] : [];
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
        <a class="navbar-brand fw-bold text-white" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">DynamoMenu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo htmlspecialchars($menuUrl); ?>">Menu</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white position-relative" href="<?php echo htmlspecialchars($panierUrl); ?>">
                        Panier
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="#contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <a class="btn btn-primary ms-lg-4" href="#contact">Contact Now</a>
        </div>
    </header>

    <main class="container-fluid px-4 py-5 hero-section">
        <?php if ($tableError || $scanError): ?>
        <div class="alert alert-warning mb-4" role="alert">
            <?php echo htmlspecialchars($tableError ?: 'QR code invalide. Rescannez le code sur votre table.'); ?>
        </div>
        <?php elseif ($tableCtx): ?>
        <div class="alert alert-success mb-4 py-2" role="status">
            Vous êtes à la <strong><?php echo htmlspecialchars($tableCtx['label']); ?></strong> — bon appétit !
        </div>
        <?php endif; ?>
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase text-warning mb-3">Commandez. Mangez. Profitez !</p>
                <h1 class="display-4 hero-title mb-4">Une nouvelle façon de commander :<br>rapide, pratique et totalement digitale</h1>
                <p class="hero-subtitle mb-4">Commandez votre repas en un clic et profitez-en dès maintenant.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($menuUrl); ?>">Commander</a>
                    <a class="btn btn-outline-light btn-lg" href="<?php echo htmlspecialchars($menuUrl); ?>">Voir menu</a>
                </div>
            </div>
            <div class="col-lg-6 text-center hero-image-col d-none d-lg-block">
                <img src="../assets/images/kombo/combo burger frites poulet.jpg" alt="Combo Burger Frites Poulet" class="img-fluid rounded-4 shadow-lg hero-home-image">
            </div>
        </div>
    </main>

    <section id="contact" class="container py-5">
        <h2 class="text-white mb-4">Contact</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                    <h3 class="h5 text-warning mb-3"><?php echo htmlspecialchars($contacts['nom'] ?? 'DynamoMenu'); ?></h3>
                    <?php if (!empty($contacts['adresse'])): ?>
                    <p class="text-white-50 mb-2"><?php echo htmlspecialchars($contacts['adresse']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contacts['horaires'])): ?>
                    <p class="text-white-50 mb-2"><strong>Horaires :</strong> <?php echo htmlspecialchars($contacts['horaires']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                    <?php if (!empty($contacts['telephone'])): ?>
                    <p class="mb-2"><a class="text-white" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contacts['telephone'])); ?>"><?php echo htmlspecialchars($contacts['telephone']); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($contacts['email'])): ?>
                    <p class="mb-2"><a class="text-white" href="mailto:<?php echo htmlspecialchars($contacts['email']); ?>"><?php echo htmlspecialchars($contacts['email']); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($contacts['whatsapp'])): ?>
                    <p class="mb-0"><a class="text-white" href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $contacts['whatsapp'])); ?>" target="_blank" rel="noopener">WhatsApp : <?php echo htmlspecialchars($contacts['whatsapp']); ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = data.count === 0 ? 'none' : 'block';
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        setInterval(updateCartBadge, 5000);
    </script>
</body>
</html>
