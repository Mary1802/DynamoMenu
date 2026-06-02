<?php

/**
 * Navigation client commune (liens avec contexte table si scan QR).
 *
 * @param 'index'|'menu'|'panier'|'' $active
 */
function render_client_nav(string $active = ''): void
{
    $tableCtx = table_session();
    $is = static fn(string $page): string => $active === $page ? ' active' : '';
    ?>
    <header class="navbar navbar-expand-lg navbar-dark px-3 px-md-4 py-2 py-md-3 client-navbar">
        <a class="navbar-brand fw-bold text-white" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">DynamoMenu</a>
        <?php if ($tableCtx): ?>
        <span class="client-table-badge d-none d-sm-inline-flex"><?php echo htmlspecialchars($tableCtx['label']); ?></span>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white<?php echo $is('index'); ?>" href="<?php echo htmlspecialchars(table_link('index.php')); ?>">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-white<?php echo $is('menu'); ?>" href="<?php echo htmlspecialchars(table_link('menu.php')); ?>"<?php echo $active === 'menu' ? ' aria-current="page"' : ''; ?>>Menu</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white position-relative<?php echo $is('panier'); ?>" href="<?php echo htmlspecialchars(table_link('panier.php')); ?>">
                        Panier
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger client-cart-badge">0</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <?php if ($tableCtx && $active !== 'menu'): ?>
            <a class="btn btn-primary ms-lg-3 mt-3 mt-lg-0 w-100 w-lg-auto" href="<?php echo htmlspecialchars(table_link('menu.php')); ?>">Commander</a>
            <?php elseif (!$tableCtx): ?>
            <span class="text-secondary small ms-lg-3 mt-2 mt-lg-0 d-block d-lg-inline">Scannez le QR de votre table</span>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($tableCtx): ?>
    <div class="client-table-strip d-sm-none">
        <i class="bi bi-qr-code-scan"></i> <?php echo htmlspecialchars($tableCtx['label']); ?> — table enregistrée
    </div>
    <?php endif;
}
