<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['admin']);

$db_config = require '../config/db.php';
require_once '../includes/dashboard_helpers.php';
require_once '../includes/table_context.php';
require_once '../services/qr_service.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur de connexion: ' . $e->getMessage());
}

table_ensure_schema($pdo);
table_assign_missing_codes($pdo);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_table'])) {
        $places = max(1, (int) ($_POST['nombre_place'] ?? 2));
        $libelle = trim($_POST['libelle'] ?? '');

        try {
            $next = (int) $pdo->query('SELECT COALESCE(MAX(num_table), 0) + 1 FROM table_restaurant')->fetchColumn();
            $code = qr_generate_table_code($next);
            $stmt = $pdo->prepare('INSERT INTO table_restaurant (num_table, nombre_place, libelle, code_table, actif) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$next, $places, $libelle ?: null, $code]);
            $message = "Table n°{$next} créée avec QR.";
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }

    if (isset($_POST['toggle_actif'])) {
        $num = (int) $_POST['num_table'];
        $stmt = $pdo->prepare('UPDATE table_restaurant SET actif = IF(actif = 1, 0, 1) WHERE num_table = ?');
        $stmt->execute([$num]);
        $message = 'Statut de la table mis à jour.';
    }

    if (isset($_POST['regenerate_code'])) {
        $num = (int) $_POST['num_table'];
        $code = qr_generate_table_code($num);
        $stmt = $pdo->prepare('UPDATE table_restaurant SET code_table = ? WHERE num_table = ?');
        $stmt->execute([$code, $num]);
        $message = "Nouveau QR généré pour la table {$num}.";
    }

    if (isset($_POST['update_table'])) {
        $num = (int) $_POST['num_table'];
        $places = max(1, (int) ($_POST['nombre_place'] ?? 2));
        $libelle = trim($_POST['libelle'] ?? '');
        $stmt = $pdo->prepare('UPDATE table_restaurant SET nombre_place = ?, libelle = ? WHERE num_table = ?');
        $stmt->execute([$places, $libelle !== '' ? $libelle : null, $num]);
        $message = "Table n°{$num} modifiée.";
    }

    if (isset($_POST['delete_table'])) {
        $num = (int) $_POST['num_table'];
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM commande WHERE num_table = ?');
            $stmt->execute([$num]);
            if ((int) $stmt->fetchColumn() > 0) {
                $error = "Impossible de supprimer la table n°{$num} : des commandes y sont encore rattachées.";
            } else {
                $pdo->prepare('DELETE FROM table_restaurant WHERE num_table = ?')->execute([$num]);
                $message = "Table n°{$num} supprimée.";
            }
        } catch (PDOException $e) {
            $error = 'Suppression impossible : ' . $e->getMessage();
        }
    }
}

$tables = $pdo->query('SELECT * FROM table_restaurant ORDER BY num_table')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Admin — Tables & QR'); ?>
    <style>
        .qr-thumb { width: 120px; height: 120px; border-radius: 8px; background: #fff; padding: 6px; }
        .code-badge { font-family: monospace; font-size: 0.8rem; color: var(--accent-warning); }
        .table-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
        .table-edit-form input.form-control { min-width: 90px; }
    </style>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
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
                <div class="brand-subtitle">Administration</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="dashboard.php"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="tables.php"><span class="nav-icon"><i class="bi bi-qr-code"></i></span><span>Tables & QR</span></a></div>
            </nav>
            <div class="sidebar-footer">
                <?php dashboard_sidebar_user_footer('admin'); ?>
            </div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Configuration</span>
                    <h1>Tables & codes QR</h1>
                    <p>Créez les tables, imprimez les QR et collez-les sur chaque table. Le client scanne une fois : il arrive sur l'accueil et sa table reste enregistrée pour menu, panier et commande.</p>
                </div>
            </header>

            <?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);background:rgba(220,53,69,.1);"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="chart-container mb-4">
                <div class="chart-title">Nouvelle table</div>
                <form method="post" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-secondary">Places</label>
                        <input type="number" name="nombre_place" class="form-control" min="1" value="4" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-secondary">Libellé (optionnel)</label>
                        <input type="text" name="libelle" class="form-control" placeholder="Terrasse A">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="create_table" class="btn-primary w-100">Créer la table + QR</button>
                    </div>
                </form>
            </div>

            <div class="chart-container">
                <div class="chart-title">Tables existantes</div>
                <div class="table-responsive-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Libellé</th>
                                <th>Places</th>
                                <th>Code unique</th>
                                <th>QR</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tables as $t):
                            $url = qr_table_entry_url($t['code_table']);
                            $qrImg = qr_image_url($url);
                            $formId = 'edit-table-' . (int) $t['num_table'];
                        ?>
                            <tr>
                                <form id="<?php echo $formId; ?>" method="post" class="d-none">
                                    <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                </form>
                                <td><?php echo (int) $t['num_table']; ?></td>
                                <td>
                                    <input type="text" name="libelle" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($t['libelle'] ?? ''); ?>" placeholder="Libellé">
                                </td>
                                <td>
                                    <input type="number" name="nombre_place" form="<?php echo $formId; ?>" class="form-control form-control-sm" min="1" value="<?php echo (int) $t['nombre_place']; ?>" style="width:70px;">
                                </td>
                                <td><span class="code-badge"><?php echo htmlspecialchars($t['code_table']); ?></span></td>
                                <td><img src="<?php echo htmlspecialchars($qrImg); ?>" alt="QR table <?php echo (int) $t['num_table']; ?>" class="qr-thumb"></td>
                                <td><?php echo (int) $t['actif'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="submit" name="update_table" form="<?php echo $formId; ?>" class="btn-primary btn-sm">Enregistrer</button>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="link-invoice">Tester</a>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                        <button type="submit" name="regenerate_code" class="btn-details btn-sm">Nouveau QR</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                        <button type="submit" name="toggle_actif" class="btn-details btn-sm"><?php echo (int) $t['actif'] ? 'Désactiver' : 'Activer'; ?></button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Supprimer définitivement la table n°<?php echo (int) $t['num_table']; ?> ?');">
                                        <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                        <button type="submit" name="delete_table" class="btn-details btn-sm" style="color:#dc3545;border-color:rgba(220,53,69,.4);">Supprimer</button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
