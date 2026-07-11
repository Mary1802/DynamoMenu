<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Application;
use App\Http\AdminPage;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}

$tableLinks = Application::getInstance()->tableCodeService();

AdminPage::shellStart(
    'Admin — Tables',
    'tables',
    'Configuration',
    'Tables du restaurant',
    'Créez les tables et configurez chaque tablette ou smartphone avec le lien d\'accueil dédié. Le client ouvre l\'accueil et commande directement depuis l\'appareil posé sur sa table.'
);
?>

<style>
    .code-badge { font-family: monospace; font-size: 0.8rem; color: var(--accent-warning); }
    .table-link-cell { max-width: 220px; font-size: 0.78rem; word-break: break-all; color: var(--text-secondary); }
</style>

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
            <button type="submit" name="create_table" class="btn-primary w-100">Créer la table</button>
        </div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title mb-3">Tables existantes</div>
    <div class="table-responsive-wrap">
        <table class="data-table admin-tables-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Libellé</th>
                    <th>Places</th>
                    <th>Code</th>
                    <th>Lien tablette</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tables as $t):
                $url = $tableLinks->tableEntryUrl((string) $t['code_table']);
                $formId = 'edit-table-' . (int) $t['num_table'];
            ?>
                <tr>
                    <td><?php echo (int) $t['num_table']; ?></td>
                    <td>
                        <input type="text" name="libelle" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($t['libelle'] ?? ''); ?>" placeholder="Libellé">
                    </td>
                    <td>
                        <input type="number" name="nombre_place" form="<?php echo $formId; ?>" class="form-control form-control-sm admin-tables-places-input" min="1" value="<?php echo (int) $t['nombre_place']; ?>">
                    </td>
                    <td><span class="code-badge"><?php echo htmlspecialchars($t['code_table']); ?></span></td>
                    <td class="table-link-cell"><a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($url); ?></a></td>
                    <td><?php echo (int) $t['actif'] ? 'Active' : 'Inactive'; ?></td>
                    <td class="admin-tables-actions-cell">
                        <form id="<?php echo $formId; ?>" method="post" class="admin-tables-edit-form">
                            <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                        </form>
                        <div class="admin-tables-actions">
                            <button type="submit" name="update_table" form="<?php echo $formId; ?>" class="btn-primary btn-sm admin-tables-btn admin-tables-btn--primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Enregistrer
                            </button>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="btn-details btn-sm admin-tables-btn">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ouvrir
                            </a>
                            <form method="post" class="admin-tables-inline-form">
                                <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                <button type="submit" name="regenerate_code" class="btn-details btn-sm admin-tables-btn">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Regénérer le code
                                </button>
                            </form>
                            <form method="post" class="admin-tables-inline-form">
                                <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                <button type="submit" name="toggle_actif" class="btn-details btn-sm admin-tables-btn">
                                    <i class="bi bi-toggle-<?php echo (int) $t['actif'] ? 'on' : 'off'; ?>" aria-hidden="true"></i>
                                    <?php echo (int) $t['actif'] ? 'Désactiver' : 'Activer'; ?>
                                </button>
                            </form>
                            <form method="post" class="admin-tables-inline-form" onsubmit="return confirm('Supprimer définitivement la table n°<?php echo (int) $t['num_table']; ?> ?');">
                                <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                <button type="submit" name="delete_table" class="btn-details btn-sm admin-tables-btn admin-tables-btn--danger">
                                    <i class="bi bi-trash" aria-hidden="true"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php AdminPage::shellEnd(); ?>
