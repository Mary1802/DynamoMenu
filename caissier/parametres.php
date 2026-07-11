<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\Dashboard;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <?php Dashboard::assetLinks('Caisse — Paramètres'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Caisse</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="paiement.php"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span>Paiements</span></a></div>
                <div class="nav-item"><a class="nav-link" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link" href="rapports.php"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span>Rapports</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php Dashboard::sidebarUserFooter('caissier'); ?></div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Paramètres</h1>
                </div>
            </header>

            <div class="dashboard-card settings-single-card">
                <section class="settings-panel-section">
                    <h3 class="settings-panel-title">Thème d'affichage</h3>
                    <p class="text-secondary small mb-2">Basculez entre le mode clair et le mode sombre.</p>
                    <?php Dashboard::themeToggle(); ?>
                </section>

                <section class="settings-panel-section">
                    <h3 class="settings-panel-title">Mon compte</h3>
                    <dl class="account-dl">
                        <dt>Nom complet</dt>
                        <dd><?php echo htmlspecialchars($account['nom'] ?? ''); ?></dd>
                        <dt>E-mail</dt>
                        <dd><?php echo htmlspecialchars($account['email'] ?? '—'); ?></dd>
                        <dt>Rôle</dt>
                        <dd><?php echo htmlspecialchars($account['role'] ?? ''); ?></dd>
                    </dl>
                </section>

                <section class="settings-panel-section">
                    <h3 class="settings-panel-title">Contacts restaurant</h3>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($contacts['nom'] ?? $contacts['nom_etablissement'] ?? 'DynamoMenu'); ?></strong></p>
                    <?php if (!empty($contacts['adresse'])): ?>
                    <p class="mb-1 text-secondary"><?php echo htmlspecialchars($contacts['adresse']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contacts['telephone'])): ?>
                    <p class="mb-1"><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contacts['telephone'])); ?>"><?php echo htmlspecialchars($contacts['telephone']); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($contacts['email'])): ?>
                    <p class="mb-0"><a href="mailto:<?php echo htmlspecialchars($contacts['email']); ?>"><?php echo htmlspecialchars($contacts['email']); ?></a></p>
                    <?php endif; ?>
                </section>

                <section class="settings-panel-section">
                    <h3 class="settings-panel-title">Horaires d'ouverture</h3>
                    <?php if (!empty($horairesLines)): ?>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($horairesLines as $line): ?>
                        <li class="text-secondary"><?php echo htmlspecialchars($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-secondary small mb-0">Non renseignés. Modifiables dans Admin → Paramètres.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    <?php Dashboard::scripts(); ?>
</body>
</html>
