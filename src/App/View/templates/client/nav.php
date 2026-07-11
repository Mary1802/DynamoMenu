<?php



$is = static fn (string $page): string => $active === $page ? ' active' : '';

?>

<header class="navbar navbar-expand-lg navbar-dark px-3 px-md-4 py-2 py-md-3 client-navbar">

    <button class="navbar-toggler d-lg-none" type="button" id="clientNavToggle" aria-controls="clientNavDrawer" aria-expanded="false" aria-label="Menu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <a class="navbar-brand fw-bold text-white ms-auto ms-lg-0" href="<?php echo htmlspecialchars($tableLink('index.php')); ?>">
        Dynamo<span>Menu</span>
        <?php if ($tableCtx): ?>
        <span class="client-table-badge">Table <?php echo (int) $tableCtx['num_table']; ?></span>
        <?php endif; ?>
    </a>

    <div class="collapse navbar-collapse d-none d-lg-flex" id="clientNavDesktop">
        <?php require __DIR__ . '/nav-links.php'; ?>

        <?php if ($tableCtx && !in_array($active, ['menu', 'panier', 'mes_commandes'], true)): ?>
        <a class="btn btn-primary btn-sm ms-lg-3 mt-3 mt-lg-0 px-3 client-nav-commander" href="<?php echo htmlspecialchars($tableLink('menu.php')); ?>">Commander</a>
        <?php elseif (!$tableCtx): ?>
        <span class="text-secondary small ms-lg-3 mt-2 mt-lg-0 d-none d-lg-inline client-nav-hint">Utilisez l'appareil de votre table</span>
        <?php endif; ?>
    </div>

</header>

<nav class="client-nav-drawer d-lg-none" id="clientNavDrawer" aria-label="Menu principal" aria-hidden="true">
    <?php require __DIR__ . '/nav-links.php'; ?>

    <div class="client-nav-drawer-footer">
        <?php if ($tableCtx && !in_array($active, ['menu', 'panier', 'mes_commandes'], true)): ?>
        <a class="btn btn-primary btn-sm px-3 client-nav-commander" href="<?php echo htmlspecialchars($tableLink('menu.php')); ?>">Commander</a>
        <?php elseif (!$tableCtx): ?>
        <p class="client-nav-hint mb-0">Utilisez l'appareil de votre table</p>
        <?php endif; ?>
    </div>
</nav>

<div class="client-nav-backdrop d-lg-none" id="clientNavBackdrop" aria-hidden="true"></div>

<script src="../assets/js/client-nav.js?v=2" defer></script>
