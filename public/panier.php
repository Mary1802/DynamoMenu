<?php
session_start();
require_once __DIR__ . '/../app/models/Plat.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = (int) ($_POST['remove_id'] ?? 0);
    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
        $message = 'Le plat a été retiré du panier.';
    }
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;

foreach ($cart as $id => $qty) {
    $plat = Plat::getById((int) $id);
    if ($plat) {
        $subtotal = $plat['price'] * $qty;
        $items[] = [
            'id' => $plat['id'],
            'name' => $plat['name'],
            'price' => $plat['price'],
            'qty' => $qty,
            'subtotal' => $subtotal,
            'image' => $plat['image'],
        ];
        $total += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - DynamoMenu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="index.php">DynamoMenu</a>
            <nav class="main-nav">
                <a href="index.php">Accueil</a>
                <a href="menu.php">Menu</a>
                <a href="panier.php" class="active">Panier</a>
                <a href="auth/login.php">Espace personnel</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="menu-preview">
            <div class="container">
                <div class="category-heading">
                    <div>
                        <span class="eyebrow">Panier</span>
                        <h1>Votre commande</h1>
                    </div>
                    <a class="btn btn-secondary" href="menu.php">Retour au menu</a>
                </div>

                <?php if ($message): ?>
                    <div class="menu-alert"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if (empty($items)): ?>
                    <div class="menu-alert">Votre panier est vide. Ajoutez des plats depuis le menu.</div>
                <?php else: ?>
                    <div class="menu-grid">
                        <?php foreach ($items as $item): ?>
                            <article class="menu-item">
                                <img src="assets/images/<?= rawurlencode($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <h3><?= htmlspecialchars($item['name']) ?></h3>
                                <p>Quantité : <?= $item['qty'] ?> | Total : <?= number_format($item['subtotal'], 2, ',', '.') ?> €</p>
                                <form method="post" action="panier.php">
                                    <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                                    <button class="btn btn-secondary" type="submit">Retirer</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-summary">
                        <div class="summary-text">
                            <h2>Total : <?= number_format($total, 2, ',', '.') ?> €</h2>
                            <p>Vous pouvez continuer vos achats ou produire votre addition auprès du caissier.</p>
                        </div>
                        <div class="summary-actions">
                            <a class="btn btn-secondary" href="menu.php">Continuer mes achats</a>
                            <a class="btn btn-primary" href="auth/login.php">Espace personnel</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

