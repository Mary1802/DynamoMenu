<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['caissier']);

require_once __DIR__ . '/../includes/dashboard_helpers.php';

$user = staff_user();
$db_config = require __DIR__ . '/../config/db.php';
try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $contacts = dashboard_contacts($pdo);
    $account = dashboard_staff_account($pdo, $user);
} catch (PDOException $e) {
    $contacts = dashboard_contacts();
    $account = [
        'nom' => $user['nom'] ?? 'Utilisateur',
        'prenom' => '',
        'nom_famille' => '',
        'email' => $user['email'] ?? '',
        'role' => staff_role_label((string) ($user['role'] ?? 'caissier')),
    ];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caisse — Paramètres'); ?>
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
            <div class="sidebar-footer"><?php dashboard_sidebar_user_footer('caissier'); ?></div>
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
                    <p class="text-secondary small mb-2">Clair : fond blanc. Sombre : fond noir.</p>
                    <div class="theme-switcher" role="group" aria-label="Thème">
                        <button type="button" class="theme-switch-btn" data-theme-set="dark"><i class="bi bi-moon-stars" aria-hidden="true"></i> Sombre</button>
                        <button type="button" class="theme-switch-btn" data-theme-set="light"><i class="bi bi-sun" aria-hidden="true"></i> Clair</button>
                    </div>
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
                    <p class="text-secondary small mb-0">Le mot de passe n'est pas affiché ici.</p>
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
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
