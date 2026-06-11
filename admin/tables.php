<?php



require_once __DIR__ . '/../includes/admin_layout.php';



$pdo = admin_init();

require_once __DIR__ . '/../includes/table_context.php';

require_once __DIR__ . '/../services/qr_service.php';



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

                            <button type="submit" name="update_table" form="<?php echo $formId; ?>" class="btn-primary btn-sm admin-tables-btn admin-tables-btn--primary">

                                <i class="bi bi-check-lg" aria-hidden="true"></i> Enregistrer

                            </button>

                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="btn-details btn-sm admin-tables-btn">

                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Tester

                            </a>

                            <form method="post" class="admin-tables-action-form">

                                <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">

                                <button type="submit" name="regenerate_code" class="btn-details btn-sm admin-tables-btn">

                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Nouveau QR

                                </button>

                            </form>

                            <form method="post" class="admin-tables-action-form">

                                <input type="hidden" name="num_table" value="<?php echo (int) $t['num_table']; ?>">

                                <button type="submit" name="toggle_actif" class="btn-details btn-sm admin-tables-btn">

                                    <i class="bi bi-toggle-<?php echo (int) $t['actif'] ? 'on' : 'off'; ?>" aria-hidden="true"></i>

                                    <?php echo (int) $t['actif'] ? 'Désactiver' : 'Activer'; ?>

                                </button>

                            </form>

                            <form method="post" class="admin-tables-action-form" onsubmit="return confirm('Supprimer définitivement la table n°<?php echo (int) $t['num_table']; ?> ?');">

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



<?php admin_shell_end(); ?>

