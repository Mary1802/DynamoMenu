<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../services/qr_service.php';

use App\Controller\Admin\TableController;

admin_init();
$result = (new TableController())->handle($_POST);
$message = $result['message'];
$error = $result['error'];
$tables = $result['tables'];

admin_shell_start(
    'Admin — Tables & QR',
    'tables',
    'Configuration',
    'Tables & codes QR',
    'Créez les tables, imprimez les QR et collez-les sur chaque table. Le client scanne une fois : il arrive sur l\'accueil et sa table reste enregistrée pour menu, panier et commande.'
);
?>

<style>
    .qr-thumb { width: 120px; height: 120px; border-radius: 8px; background: #fff; padding: 6px; }
    .code-badge { font-family: monospace; font-size: 0.8rem; color: var(--accent-warning); }
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

            <button type="submit" name="create_table" class="btn-primary w-100">Créer la table + QR</button>

        </div>

    </form>

</div>



<div class="chart-container">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="chart-title mb-0">Tables existantes</div>
        <?php if ($tables !== []): ?>
        <a href="imprimer_qr.php?all=1" target="_blank" rel="noopener" class="btn-primary btn-sm">
            <i class="bi bi-printer" aria-hidden="true"></i> Imprimer tous les QR
        </a>
        <?php endif; ?>
    </div>

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

                    <td><?php echo (int) $t['num_table']; ?></td>

                    <td>

                        <input type="text" name="libelle" form="<?php echo $formId; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($t['libelle'] ?? ''); ?>" placeholder="Libellé">

                    </td>

                    <td>

                        <input type="number" name="nombre_place" form="<?php echo $formId; ?>" class="form-control form-control-sm admin-tables-places-input" min="1" value="<?php echo (int) $t['nombre_place']; ?>">

                    </td>

                    <td><span class="code-badge"><?php echo htmlspecialchars($t['code_table']); ?></span></td>

                    <td><img src="<?php echo htmlspecialchars($qrImg); ?>" alt="QR table <?php echo (int) $t['num_table']; ?>" class="qr-thumb"></td>

                    <td><?php echo (int) $t['actif'] ? 'Active' : 'Inactive'; ?></td>

                    <td class="admin-tables-actions-cell">
                        <form id="<?php echo $formId; ?>" method="post" class="admin-tables-edit-form">
                            <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                        </form>

                        <div class="admin-tables-actions">
                            <div class="admin-tables-actions-row">
                                <button type="submit" name="update_table" form="<?php echo $formId; ?>" class="btn-primary btn-sm admin-tables-btn admin-tables-btn--primary">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> Enregistrer
                                </button>
                            </div>

                            <div class="admin-tables-actions-row admin-tables-actions-row--qr">
                                <a href="imprimer_qr.php?table=<?php echo (int) $t['num_table']; ?>" target="_blank" rel="noopener" class="btn-details btn-sm admin-tables-btn">
                                    <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
                                </a>
                                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="btn-details btn-sm admin-tables-btn">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Tester
                                </a>
                                <form method="post" class="admin-tables-inline-form">
                                    <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">
                                    <button type="submit" name="regenerate_code" class="btn-details btn-sm admin-tables-btn">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Regénérer
                                    </button>
                                </form>
                            </div>

                            <div class="admin-tables-actions-row admin-tables-actions-row--manage">
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
                        </div>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>



<?php admin_shell_end(); ?>

