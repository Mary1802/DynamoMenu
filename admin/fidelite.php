<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/fidelity_service.php';

$pdo = admin_pdo();
fidelity_ensure($pdo);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_reward'])) {
        $stmt = $pdo->prepare('INSERT INTO recompense_fidelite (libelle, description, points_requis, type_recompense, valeur, actif) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->execute([
            trim($_POST['libelle']),
            trim($_POST['description'] ?? ''),
            (int) $_POST['points_requis'],
            $_POST['type_recompense'],
            (float) $_POST['valeur'],
        ]);
        admin_log($pdo, 'recompense_create', 'Récompense : ' . $_POST['libelle'], 'fidelite');
        $message = 'Récompense créée.';
    }
    if (isset($_POST['update_reward'])) {
        $stmt = $pdo->prepare('UPDATE recompense_fidelite SET libelle = ?, description = ?, points_requis = ?, type_recompense = ?, valeur = ?, actif = ? WHERE id_recompense = ?');
        $stmt->execute([
            trim($_POST['libelle']),
            trim($_POST['description'] ?? ''),
            (int) $_POST['points_requis'],
            $_POST['type_recompense'],
            (float) $_POST['valeur'],
            isset($_POST['actif']) ? 1 : 0,
            (int) $_POST['id_recompense'],
        ]);
        $message = 'Récompense mise à jour.';
    }
    if (isset($_POST['delete_reward'])) {
        $pdo->prepare('DELETE FROM recompense_fidelite WHERE id_recompense = ?')->execute([(int) $_POST['id_recompense']]);
        $message = 'Récompense supprimée.';
    }
}

$rewards = fidelity_list_rewards($pdo, false);
$types = ['pourcentage' => '% réduction', 'montant_fixe' => 'Montant fixe €', 'cadeau' => 'Cadeau'];

admin_shell_start('Admin — Fidélité', 'fidelite', 'Fidélité', 'Récompenses', 'Définissez les paliers échangeables contre des points.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Nouvelle récompense</div>
    <form method="post" class="row g-3">
        <div class="col-md-3"><input type="text" name="libelle" class="form-control" placeholder="Libellé" required></div>
        <div class="col-md-2"><input type="number" name="points_requis" class="form-control" placeholder="Points" min="1" required></div>
        <div class="col-md-2">
            <select name="type_recompense" class="form-select">
                <?php foreach ($types as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo $l; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" name="valeur" class="form-control" placeholder="Valeur" value="0"></div>
        <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description"></div>
        <div class="col-12"><button type="submit" name="add_reward" class="btn-primary">Créer</button></div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Récompenses actives</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead><tr><th>Libellé</th><th>Points</th><th>Type</th><th>Valeur</th><th>Actif</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rewards as $r): ?>
            <tr>
                <form method="post">
                    <td><input type="text" name="libelle" class="form-control form-control-sm" value="<?php echo htmlspecialchars($r['libelle']); ?>"></td>
                    <td><input type="number" name="points_requis" class="form-control form-control-sm" value="<?php echo (int) $r['points_requis']; ?>"></td>
                    <td>
                        <select name="type_recompense" class="form-select form-select-sm">
                            <?php foreach ($types as $k => $l): ?>
                            <option value="<?php echo $k; ?>"<?php echo $r['type_recompense'] === $k ? ' selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="valeur" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $r['valeur']); ?>"></td>
                    <td><input type="checkbox" name="actif" value="1"<?php echo (int) $r['actif'] ? ' checked' : ''; ?>></td>
                    <td class="d-flex gap-1">
                        <input type="hidden" name="id_recompense" value="<?php echo (int) $r['id_recompense']; ?>">
                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($r['description'] ?? ''); ?>">
                        <button type="submit" name="update_reward" class="btn-details btn-sm">OK</button>
                        <button type="submit" name="delete_reward" class="btn-details btn-sm" onclick="return confirm('Supprimer ?');">×</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-secondary small mt-3 mb-0">Paliers : Bronze &lt; 50 pts, Argent 50–149, Or 150+.</p>
</div>
<?php admin_shell_end(); ?>
