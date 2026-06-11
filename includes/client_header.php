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
            <?php if ($tableCtx && !in_array($active, ['menu', 'panier'], true)): ?>
            <a class="btn btn-primary ms-lg-3 mt-3 mt-lg-0 w-100 w-lg-auto" href="<?php echo htmlspecialchars(table_link('menu.php')); ?>">Commander</a>
            <?php elseif (!$tableCtx): ?>
            <span class="text-secondary small ms-lg-3 mt-2 mt-lg-0 d-block d-lg-inline">Scannez le QR de votre table</span>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($tableCtx): ?>
    <?php render_client_table_strip($tableCtx); ?>
    <?php endif;
}

/**
 * Bandeau accueil : table enregistrée + bon appétit.
 *
 * @param array{num_table:int,label:string,code_table?:string|null} $tableCtx
 */
function render_client_table_welcome(array $tableCtx): void
{
    $numTable = (int) ($tableCtx['num_table'] ?? 0);
    $label = htmlspecialchars((string) ($tableCtx['label'] ?? ('Table ' . $numTable)));
    ?>
    <div class="client-table-welcome mb-4" role="status" aria-live="polite">
        <div class="client-table-welcome__accent" aria-hidden="true"></div>
        <div class="client-table-welcome__inner">
            <div class="client-table-welcome__icon" aria-hidden="true">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="client-table-welcome__content">
                <p class="client-table-welcome__eyebrow">Bienvenue</p>
                <p class="client-table-welcome__title"><?php echo $label; ?></p>
                <p class="client-table-welcome__text">Bon appétit — commandez depuis le menu, nous préparons votre commande pour cette table.</p>
            </div>
            <div class="client-table-welcome__table-pill" aria-label="Numéro de table <?php echo $numTable; ?>">
                <span class="client-table-welcome__table-pill-label">Table</span>
                <span class="client-table-welcome__table-pill-num"><?php echo $numTable > 0 ? $numTable : '—'; ?></span>
            </div>
        </div>
    </div>
    <?php
}

function render_client_table_error(string $message): void
{
    ?>
    <div class="client-table-welcome client-table-welcome--error mb-4" role="alert">
        <div class="client-table-welcome__accent" aria-hidden="true"></div>
        <div class="client-table-welcome__inner">
            <div class="client-table-welcome__icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="client-table-welcome__content">
                <p class="client-table-welcome__eyebrow">Scan requis</p>
                <p class="client-table-welcome__title">Table non reconnue</p>
                <p class="client-table-welcome__text"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array{num_table:int,label:string} $tableCtx
 */
function render_client_table_strip(array $tableCtx): void
{
    $numTable = (int) ($tableCtx['num_table'] ?? 0);
    ?>
    <div class="client-table-strip d-lg-none" role="status">
        <span class="client-table-strip__pill">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            <span class="client-table-strip__label">Table <?php echo $numTable > 0 ? $numTable : htmlspecialchars((string) $tableCtx['label']); ?></span>
        </span>
        <span class="client-table-strip__msg">Bon appétit</span>
    </div>
    <?php
}
