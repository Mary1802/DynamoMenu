<?php

require_once __DIR__ . '/../includes/admin_layout.php';

$pdo = admin_pdo();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_plat'])) {
            $stmt = $pdo->prepare('INSERT INTO plat (nom_plat, prix_unitaire, categorie) VALUES (?, ?, ?)');
            $stmt->execute([trim($_POST['nom_plat']), (float) $_POST['prix_unitaire'], trim($_POST['categorie'] ?? '')]);
            admin_log($pdo, 'plat_create', 'Plat ajouté : ' . $_POST['nom_plat']);
            $message = 'Plat ajouté.';
        }
        if (isset($_POST['update_plat'])) {
            $stmt = $pdo->prepare('UPDATE plat SET nom_plat = ?, prix_unitaire = ?, categorie = ? WHERE id_plat = ?');
            $stmt->execute([trim($_POST['nom_plat']), (float) $_POST['prix_unitaire'], trim($_POST['categorie'] ?? ''), (int) $_POST['id_plat']]);
            $message = 'Plat mis à jour.';
        }
        if (isset($_POST['delete_plat'])) {
            $pdo->prepare('DELETE FROM plat WHERE id_plat = ?')->execute([(int) $_POST['id_plat']]);
            $message = 'Plat supprimé.';
        }
        if (isset($_POST['add_boisson'])) {
            $stmt = $pdo->prepare('INSERT INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['nom_boisson']),
                $_POST['type_boisson'] ?? 'soda',
                trim($_POST['dosage'] ?? ''),
                (int) ($_POST['quantite_boisson'] ?? 0),
                trim($_POST['options_fruits'] ?? ''),
            ]);
            $message = 'Boisson ajoutée.';
        }
        if (isset($_POST['update_boisson'])) {
            $stmt = $pdo->prepare('UPDATE boisson SET nom_boisson = ?, type_boisson = ?, dosage = ?, quantite_boisson = ?, options_fruits = ? WHERE id_boisson = ?');
            $stmt->execute([
                trim($_POST['nom_boisson']),
                $_POST['type_boisson'] ?? 'soda',
                trim($_POST['dosage'] ?? ''),
                (int) ($_POST['quantite_boisson'] ?? 0),
                trim($_POST['options_fruits'] ?? ''),
                (int) $_POST['id_boisson'],
            ]);
            $message = 'Boisson mise à jour.';
        }
        if (isset($_POST['delete_boisson'])) {
            $pdo->prepare('DELETE FROM boisson WHERE id_boisson = ?')->execute([(int) $_POST['id_boisson']]);
            $message = 'Boisson supprimée.';
        }
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$plats = $pdo->query('SELECT * FROM plat ORDER BY categorie, nom_plat')->fetchAll(PDO::FETCH_ASSOC);
$boissons = $pdo->query('SELECT * FROM boisson ORDER BY nom_boisson')->fetchAll(PDO::FETCH_ASSOC);
$typesBoisson = ['soda', 'eau', 'jus', 'alcool'];

admin_shell_start('Admin — Menu', 'plats', 'Carte', 'Plats & boissons', 'Gérez les articles en base (le menu client peut rester synchronisé manuellement).');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Nouveau plat</div>
    <form method="post" class="row g-3">
        <div class="col-md-4"><input type="text" name="nom_plat" class="form-control" placeholder="Nom" required></div>
        <div class="col-md-2"><input type="number" step="1" name="prix_unitaire" class="form-control" placeholder="Prix (FC)" required></div>
        <div class="col-md-3"><input type="text" name="categorie" class="form-control" placeholder="Catégorie"></div>
        <div class="col-md-3"><button type="submit" name="add_plat" class="btn-primary w-100">Ajouter plat</button></div>
    </form>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Plats</div>
    <div class="menu-edit-mobile">
        <?php foreach ($plats as $p): ?>
        <div class="menu-edit-card">
            <form method="post">
                <div class="mb-2"><label class="form-label text-secondary small">Nom</label><input type="text" name="nom_plat" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['nom_plat']); ?>"></div>
                <div class="mb-2"><label class="form-label text-secondary small">Prix (FC)</label><input type="number" step="1" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $p['prix_unitaire']); ?>"></div>
                <div class="mb-2"><label class="form-label text-secondary small">Catégorie</label><input type="text" name="categorie" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['categorie'] ?? ''); ?>"></div>
                <input type="hidden" name="id_plat" value="<?php echo (int) $p['id_plat']; ?>">
                <div class="row-actions">
                    <button type="submit" name="update_plat" class="btn-details btn-sm">Enregistrer</button>
                    <button type="submit" name="delete_plat" class="btn-details btn-sm" onclick="return confirm('Supprimer ce plat ?');">Suppr.</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="table-responsive-wrap menu-edit-desktop">
        <table class="data-table">
            <thead><tr><th>Nom</th><th>Prix</th><th>Catégorie</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($plats as $p): ?>
            <tr>
                <form method="post">
                    <td><input type="text" name="nom_plat" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['nom_plat']); ?>"></td>
                    <td><input type="number" step="0.01" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $p['prix_unitaire']); ?>"></td>
                    <td><input type="text" name="categorie" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['categorie'] ?? ''); ?>"></td>
                    <td class="d-flex gap-1">
                        <input type="hidden" name="id_plat" value="<?php echo (int) $p['id_plat']; ?>">
                        <button type="submit" name="update_plat" class="btn-details btn-sm">Enregistrer</button>
                        <button type="submit" name="delete_plat" class="btn-details btn-sm" onclick="return confirm('Supprimer ce plat ?');">Suppr.</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Nouvelle boisson</div>
    <form method="post" class="row g-3">
        <div class="col-md-3"><input type="text" name="nom_boisson" class="form-control" placeholder="Nom" required></div>
        <div class="col-md-2">
            <select name="type_boisson" class="form-select">
                <?php foreach ($typesBoisson as $t): ?><option value="<?php echo $t; ?>"><?php echo $t; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input type="text" name="dosage" class="form-control" placeholder="Dosage"></div>
        <div class="col-md-2"><input type="number" name="quantite_boisson" class="form-control" placeholder="Stock" value="0"></div>
        <div class="col-md-3"><button type="submit" name="add_boisson" class="btn-primary w-100">Ajouter boisson</button></div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Boissons</div>
    <div class="menu-edit-mobile">
        <?php foreach ($boissons as $b): ?>
        <div class="menu-edit-card">
            <form method="post">
                <div class="mb-2"><input type="text" name="nom_boisson" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['nom_boisson']); ?>"></div>
                <div class="mb-2">
                    <select name="type_boisson" class="form-select form-select-sm">
                        <?php foreach ($typesBoisson as $t): ?>
                        <option value="<?php echo $t; ?>"<?php echo ($b['type_boisson'] ?? '') === $t ? ' selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><input type="text" name="dosage" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['dosage'] ?? ''); ?>" placeholder="Dosage"></div>
                <div class="mb-2"><input type="number" name="quantite_boisson" class="form-control form-control-sm" value="<?php echo (int) $b['quantite_boisson']; ?>" placeholder="Stock"></div>
                <input type="hidden" name="id_boisson" value="<?php echo (int) $b['id_boisson']; ?>">
                <input type="hidden" name="options_fruits" value="<?php echo htmlspecialchars($b['options_fruits'] ?? ''); ?>">
                <div class="row-actions">
                    <button type="submit" name="update_boisson" class="btn-details btn-sm">Enregistrer</button>
                    <button type="submit" name="delete_boisson" class="btn-details btn-sm" onclick="return confirm('Supprimer ?');">Suppr.</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="table-responsive-wrap menu-edit-desktop">
        <table class="data-table">
            <thead><tr><th>Nom</th><th>Type</th><th>Dosage</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($boissons as $b): ?>
            <tr>
                <form method="post">
                    <td><input type="text" name="nom_boisson" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['nom_boisson']); ?>"></td>
                    <td>
                        <select name="type_boisson" class="form-select form-select-sm">
                            <?php foreach ($typesBoisson as $t): ?>
                            <option value="<?php echo $t; ?>"<?php echo ($b['type_boisson'] ?? '') === $t ? ' selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="dosage" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['dosage'] ?? ''); ?>"></td>
                    <td><input type="number" name="quantite_boisson" class="form-control form-control-sm" value="<?php echo (int) $b['quantite_boisson']; ?>"></td>
                    <td class="d-flex gap-1">
                        <input type="hidden" name="id_boisson" value="<?php echo (int) $b['id_boisson']; ?>">
                        <input type="hidden" name="options_fruits" value="<?php echo htmlspecialchars($b['options_fruits'] ?? ''); ?>">
                        <button type="submit" name="update_boisson" class="btn-details btn-sm">Enregistrer</button>
                        <button type="submit" name="delete_boisson" class="btn-details btn-sm" onclick="return confirm('Supprimer ?');">Suppr.</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
