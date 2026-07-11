<?php

$is = static fn (string $page): string => $active === $page ? ' active' : '';

?>
<ul class="navbar-nav ms-auto align-items-lg-center client-nav-list">

    <li class="nav-item">
        <a class="nav-link text-white<?php echo $is('index'); ?>" href="<?php echo htmlspecialchars($tableLink('index.php')); ?>">
            <i class="bi bi-house-door client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">Accueil</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white<?php echo $is('menu'); ?>" href="<?php echo htmlspecialchars($tableLink('menu.php')); ?>"<?php echo $active === 'menu' ? ' aria-current="page"' : ''; ?>>
            <i class="bi bi-journal-text client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">Menu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="<?php echo htmlspecialchars($aboutHref); ?>">
            <i class="bi bi-info-circle client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">À propos</span>
        </a>
    </li>

    <?php if (!empty($hasOrders)): ?>
    <li class="nav-item">
        <a class="nav-link text-white<?php echo $is('mes_commandes'); ?>" href="<?php echo htmlspecialchars($mesCommandesUrl ?? $tableLink('mes_commandes.php')); ?>">
            <i class="bi bi-receipt client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">Mes commandes</span>
        </a>
    </li>
    <?php endif; ?>

    <li class="nav-item">
        <a class="nav-link text-white client-nav-link-cart<?php echo $is('panier'); ?>" href="<?php echo htmlspecialchars($tableLink('panier.php')); ?>">
            <i class="bi bi-bag client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">Panier</span>
            <span class="badge rounded-pill bg-danger client-cart-badge" data-cart-count>0</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="../login.php">
            <i class="bi bi-person-badge client-nav-icon d-lg-none" aria-hidden="true"></i>
            <span class="client-nav-label">Employé</span>
        </a>
    </li>

</ul>
