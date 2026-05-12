<?php
session_start();
require_once __DIR__ . '/../app/models/Plat.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $platId = (int) ($_POST['plat_id'] ?? 0);
    $plat = Plat::getById($platId);
    if ($plat) {
        if (!isset($_SESSION['cart'][$platId])) {
            $_SESSION['cart'][$platId] = 0;
        }
        $_SESSION['cart'][$platId]++;
        $message = sprintf('%s a bien été ajouté au panier.', $plat['name']);
    } else {
        $message = 'Le plat sélectionné est introuvable.';
    }
}

$categories = Plat::getByCategory();
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - DynamoMenu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="index.php">DynamoMenu</a>
            <nav class="main-nav">
                <a href="index.php">Accueil</a>
                <a href="menu.php" class="active">Menu</a>
                <a href="panier.php">Panier (<?= $cartCount ?>)</a>
                <a href="auth/login.php">Espace personnel</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="menu-top">
            <div class="container">
                <div class="cart-panel">
                    <div>
                        <span class="eyebrow">Menu</span>
                        <h1>Choisissez votre plat</h1>
                    </div>
                    <div>
                        <a class="btn btn-secondary" href="panier.php">Voir le panier (<?= $cartCount ?>)</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="menu-alert"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php foreach ($categories as $category => $plats): ?>
                    <section class="category-section">
                        <div class="category-heading">
                            <div>
                                <span class="eyebrow"><?= htmlspecialchars($category) ?></span>
                                <h2><?= htmlspecialchars($category) ?></h2>
                            </div>
                        </div>
                        <div class="menu-grid">
                            <?php foreach ($plats as $plat): ?>
                                <article class="menu-item">
                                    <img src="assets/images/<?= rawurlencode($plat['image']) ?>" alt="<?= htmlspecialchars($plat['name']) ?>">
                                    <h3><?= htmlspecialchars($plat['name']) ?></h3>
                                    <p><?= htmlspecialchars($plat['description']) ?></p>
                                    <div class="item-bottom">
                                        <span class="menu-badge"><?= number_format($plat['price'], 2, ',', '.') ?> €</span>
                                        <form method="post" action="menu.php">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="plat_id" value="<?= $plat['id'] ?>">
                                            <button class="btn btn-primary" type="submit">Ajouter au panier</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
