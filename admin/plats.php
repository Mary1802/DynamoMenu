<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/schema_upgrade.php';
require_once __DIR__ . '/../includes/menu_image_index.php';

$pdo = admin_init();
schema_upgrade($pdo);
$message = '';
$error = '';

/**
 * @param array<string, mixed> $file
 * @return string|null
 */
function upload_menu_image(array $file): ?string
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'] ?? '', $allowed, true)) {
        return null;
    }

    $uploadDir = dirname(__DIR__) . '/assets/images/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $basename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = $basename . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'assets/images/uploads/' . $filename;
}

function resolve_type_boisson_id(PDO $pdo, string $nomType): ?int
{
    $nomType = trim($nomType);
    if ($nomType === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id_type FROM type_boisson WHERE nom_type = ?');
    $stmt->execute([$nomType]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['id_type'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_plat'])) {
            $imagePath = upload_menu_image($_FILES['image_plat'] ?? []);
            $quantite = isset($_POST['quantite_plat']) ? (int) $_POST['quantite_plat'] : 0;
            $stmt = $pdo->prepare('INSERT INTO plat (nom_plat, prix_unitaire, categorie, quantite_plat, image_url) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([trim($_POST['nom_plat']), (float) $_POST['prix_unitaire'], trim($_POST['categorie'] ?? ''), $quantite, $imagePath]);
            admin_log($pdo, 'plat_create', 'Plat ajouté : ' . $_POST['nom_plat']);
            $message = 'Plat ajouté.';
        }
        if (isset($_POST['update_plat'])) {
            $imagePath = upload_menu_image($_FILES['image_plat'] ?? []);
            $quantite = isset($_POST['quantite_plat']) ? (int) $_POST['quantite_plat'] : 0;
            if ($imagePath !== null) {
                $stmt = $pdo->prepare('UPDATE plat SET nom_plat = ?, prix_unitaire = ?, categorie = ?, quantite_plat = ?, image_url = ? WHERE id_plat = ?');
                $stmt->execute([trim($_POST['nom_plat']), (float) $_POST['prix_unitaire'], trim($_POST['categorie'] ?? ''), $quantite, $imagePath, (int) $_POST['id_plat']]);
            } else {
                $stmt = $pdo->prepare('UPDATE plat SET nom_plat = ?, prix_unitaire = ?, categorie = ?, quantite_plat = ? WHERE id_plat = ?');
                $stmt->execute([trim($_POST['nom_plat']), (float) $_POST['prix_unitaire'], trim($_POST['categorie'] ?? ''), $quantite, (int) $_POST['id_plat']]);
            }
            $message = 'Plat mis à jour.';
        }
        if (isset($_POST['delete_plat'])) {
            $pdo->prepare('DELETE FROM plat WHERE id_plat = ?')->execute([(int) $_POST['id_plat']]);
            $message = 'Plat supprimé.';
        }
        if (isset($_POST['add_boisson'])) {
            $idType = resolve_type_boisson_id($pdo, (string) ($_POST['type_boisson'] ?? ''));
            if ($idType === null) {
                $error = 'Choisissez un type de boisson existant.';
            } else {
                $imagePath = upload_menu_image($_FILES['image_boisson'] ?? []);
                $prix = isset($_POST['prix_unitaire']) ? (float) $_POST['prix_unitaire'] : 0.0;
                $stmt = $pdo->prepare('INSERT INTO boisson (nom_boisson, id_type, dosage, quantite_boisson, prix_unitaire, options_fruits, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    trim($_POST['nom_boisson']),
                    $idType,
                    trim($_POST['dosage'] ?? ''),
                    (int) ($_POST['quantite_boisson'] ?? 0),
                    $prix,
                    trim($_POST['options_fruits'] ?? ''),
                    $imagePath,
                ]);
                $message = 'Boisson ajoutée.';
            }
        }
        if (isset($_POST['update_boisson'])) {
            $idType = resolve_type_boisson_id($pdo, (string) ($_POST['type_boisson'] ?? ''));
            if ($idType === null) {
                $error = 'Choisissez un type de boisson existant.';
            } else {
                $imagePath = upload_menu_image($_FILES['image_boisson'] ?? []);
                $prix = isset($_POST['prix_unitaire']) ? (float) $_POST['prix_unitaire'] : 0.0;
                if ($imagePath !== null) {
                    $stmt = $pdo->prepare('UPDATE boisson SET nom_boisson = ?, id_type = ?, dosage = ?, quantite_boisson = ?, prix_unitaire = ?, options_fruits = ?, image_url = ? WHERE id_boisson = ?');
                    $stmt->execute([
                        trim($_POST['nom_boisson']),
                        $idType,
                        trim($_POST['dosage'] ?? ''),
                        (int) ($_POST['quantite_boisson'] ?? 0),
                        $prix,
                        trim($_POST['options_fruits'] ?? ''),
                        $imagePath,
                        (int) $_POST['id_boisson'],
                    ]);
                } else {
                    $stmt = $pdo->prepare('UPDATE boisson SET nom_boisson = ?, id_type = ?, dosage = ?, quantite_boisson = ?, prix_unitaire = ?, options_fruits = ? WHERE id_boisson = ?');
                    $stmt->execute([
                        trim($_POST['nom_boisson']),
                        $idType,
                        trim($_POST['dosage'] ?? ''),
                        (int) ($_POST['quantite_boisson'] ?? 0),
                        $prix,
                        trim($_POST['options_fruits'] ?? ''),
                        (int) $_POST['id_boisson'],
                    ]);
                }
                $message = 'Boisson mise à jour.';
            }
        }
        if (isset($_POST['delete_boisson'])) {
            $pdo->prepare('DELETE FROM boisson WHERE id_boisson = ?')->execute([(int) $_POST['id_boisson']]);
            $message = 'Boisson supprimée.';
        }
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$q = $_GET['q'] ?? '';
$platsQuery = 'SELECT * FROM plat';
$boissonsQuery = 'SELECT b.*, tb.nom_type AS type_boisson FROM boisson b';
// Detect available columns on boisson to avoid referencing missing columns
$boissonCols = array_column($pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
$typeBoissonTableExists = count($pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)) > 0;
$boissonJoin = '';
if ($typeBoissonTableExists && in_array('id_type', $boissonCols, true)) {
    $boissonJoin = ' LEFT JOIN type_boisson tb ON b.id_type = tb.id_type';
} else {
    $boissonsQuery = 'SELECT * FROM boisson';
}
if ($q !== '') {
    $qpattern = '%' . $q . '%';
    $platsQuery .= ' WHERE nom_plat LIKE ? OR categorie LIKE ?';
    if ($typeBoissonTableExists && in_array('id_type', $boissonCols, true)) {
        $stmt = $pdo->prepare($platsQuery . ' ORDER BY categorie, nom_plat');
        $stmt->execute([$qpattern, $qpattern]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare($boissonsQuery . $boissonJoin . ' WHERE nom_boisson LIKE ? ORDER BY nom_boisson');
        $stmt->execute([$qpattern]);
        $boissons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: search by name, dosage, options_fruits
        $boissonsQuery .= ' WHERE nom_boisson LIKE ? OR dosage LIKE ? OR options_fruits LIKE ?';
        $stmt = $pdo->prepare($platsQuery . ' ORDER BY categorie, nom_plat');
        $stmt->execute([$qpattern, $qpattern]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare($boissonsQuery . ' ORDER BY nom_boisson');
        $stmt->execute([$qpattern, $qpattern, $qpattern]);
        $boissons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $plats = $pdo->query($platsQuery . ' ORDER BY categorie, nom_plat')->fetchAll(PDO::FETCH_ASSOC);
    $boissons = $pdo->query($boissonsQuery . $boissonJoin . ' ORDER BY nom_boisson')->fetchAll(PDO::FETCH_ASSOC);
}
$categoriePlatOptions = dashboard_plat_categories($pdo);
$typesBoissonOptions = dashboard_boisson_types($pdo);

admin_shell_start('Admin — Menu', 'plats', 'Carte', 'Plats & boissons', 'Gérez les articles en base (le menu client peut rester synchronisé manuellement).');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);"<?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par nom ou catégorie..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($q !== ''): ?>
        <div class="col-md-2">
            <a href="plats.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Nouveau plat</div>
    <form method="post" class="row g-3 align-items-end menu-add-form" enctype="multipart/form-data">
        <div class="col-sm-6 col-lg-3">
            <input type="text" name="nom_plat" class="form-control" placeholder="Nom" required>
        </div>
        <div class="col-6 col-lg-2">
            <input type="number" step="0.01" name="prix_unitaire" class="form-control" placeholder="Prix (FC)" required>
        </div>
        <div class="col-6 col-lg-2">
            <?php dashboard_render_plat_categorie_select('categorie', '', $categoriePlatOptions, false, true); ?>
        </div>
        <div class="col-6 col-lg-1">
            <input type="number" name="quantite_plat" class="form-control" placeholder="Stock" value="0" min="0">
        </div>
        <div class="col-12 col-lg-4 menu-add-form__actions">
            <div class="menu-add-form__actions-inner">
                <input type="file" name="image_plat" class="file-input-btn-only menu-add-form__file" accept="image/*" title="Image du plat">
                <button type="submit" name="add_plat" class="btn-primary menu-add-form__submit">Ajouter plat</button>
            </div>
        </div>
    </form>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Plats</div>
    <div class="menu-edit-mobile">
        <?php foreach ($plats as $p): ?>
        <div class="menu-edit-card">
            <form method="post" enctype="multipart/form-data">
                <?php if (!empty($p['image_url'])): ?>
                <img src="../<?php echo htmlspecialchars(ltrim((string) normalize_menu_image_path($p['image_url'] ?? ''), '/')); ?>" alt="" class="menu-edit-thumb" width="120" height="72">
                <?php endif; ?>
                <div class="mb-2"><label class="form-label text-secondary small">Nom</label><input type="text" name="nom_plat" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['nom_plat']); ?>"></div>
                <div class="mb-2"><label class="form-label text-secondary small">Prix (FC)</label><input type="number" step="1" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $p['prix_unitaire']); ?>"></div>
                <div class="mb-2">
                    <label class="form-label text-secondary small">Catégorie</label>
                    <?php dashboard_render_plat_categorie_select('categorie', (string) ($p['categorie'] ?? ''), $categoriePlatOptions, true); ?>
                </div>
                <div class="mb-2"><input type="file" name="image_plat" class="file-input-btn-only file-input-btn-only--sm" accept="image/*"></div>
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
            <thead><tr><th>Image</th><th>Nom</th><th>Catégorie</th><th>Stock</th><th>Prix</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($plats as $p): ?>
            <tr>
                <form method="post" enctype="multipart/form-data">
                        <td class="menu-edit-img-cell">
                            <?php if (!empty($p['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars(ltrim((string) normalize_menu_image_path($p['image_url'] ?? ''), '/')); ?>" alt="" class="menu-edit-thumb" width="56" height="56">
                            <?php endif; ?>
                            <input type="file" name="image_plat" class="file-input-btn-only file-input-btn-only--sm mt-1" accept="image/*">
                        </td>
                        <td><input type="text" name="nom_plat" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['nom_plat']); ?>"></td>
                        <td><?php dashboard_render_plat_categorie_select('categorie', (string) ($p['categorie'] ?? ''), $categoriePlatOptions, true); ?></td>
                        <td><input type="number" name="quantite_plat" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($p['quantite_plat'] ?? 0)); ?>"></td>
                        <td><input type="number" step="0.01" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $p['prix_unitaire']); ?>"></td>
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
    <form method="post" class="row g-3 align-items-end menu-add-form" enctype="multipart/form-data">
        <div class="col-sm-6 col-lg-2">
            <input type="text" name="nom_boisson" class="form-control" placeholder="Nom" required>
        </div>
        <div class="col-6 col-lg-2">
            <?php dashboard_render_boisson_type_select('type_boisson', '', $typesBoissonOptions, false, true); ?>
        </div>
        <div class="col-6 col-lg-2">
            <input type="text" name="dosage" class="form-control" placeholder="Dosage">
        </div>
        <div class="col-6 col-lg-1">
            <input type="number" step="0.01" name="prix_unitaire" class="form-control" placeholder="Prix (FC)" value="0" min="0">
        </div>
        <div class="col-6 col-lg-1">
            <input type="number" name="quantite_boisson" class="form-control" placeholder="Stock" value="0" min="0">
        </div>
        <div class="col-12 col-lg-4 menu-add-form__actions">
            <div class="menu-add-form__actions-inner">
                <input type="file" name="image_boisson" class="file-input-btn-only menu-add-form__file" accept="image/*" title="Image de la boisson">
                <button type="submit" name="add_boisson" class="btn-primary menu-add-form__submit">Ajouter</button>
            </div>
        </div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Boissons</div>
    <div class="menu-edit-mobile">
        <?php foreach ($boissons as $b): ?>
        <div class="menu-edit-card">
            <form method="post" enctype="multipart/form-data">
                <?php if (!empty($b['image_url'])): ?>
                <img src="../<?php echo htmlspecialchars(ltrim((string) normalize_menu_image_path($b['image_url'] ?? ''), '/')); ?>" alt="" class="menu-edit-thumb" width="120" height="72">
                <?php endif; ?>
                <div class="mb-2"><input type="text" name="nom_boisson" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['nom_boisson']); ?>"></div>
                <div class="mb-2"><input type="number" step="0.01" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($b['prix_unitaire'] ?? 0)); ?>" placeholder="Prix (FC)"></div>
                <div class="mb-2">
                    <label class="form-label text-secondary small">Type</label>
                    <?php dashboard_render_boisson_type_select('type_boisson', (string) ($b['type_boisson'] ?? ''), $typesBoissonOptions, true); ?>
                </div>
                <div class="mb-2"><input type="text" name="dosage" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['dosage'] ?? ''); ?>" placeholder="Dosage"></div>
                <div class="mb-2"><input type="number" name="quantite_boisson" class="form-control form-control-sm" value="<?php echo (int) $b['quantite_boisson']; ?>" placeholder="Stock"></div>
                <div class="mb-2"><input type="file" name="image_boisson" class="file-input-btn-only file-input-btn-only--sm" accept="image/*"></div>
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
            <thead><tr><th>Image</th><th>Nom</th><th>Prix</th><th>Type</th><th>Dosage</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($boissons as $b): ?>
            <tr>
                <form method="post" enctype="multipart/form-data">
                    <td class="menu-edit-img-cell">
                        <?php if (!empty($b['image_url'])): ?>
                        <img src="../<?php echo htmlspecialchars(ltrim((string) normalize_menu_image_path($b['image_url'] ?? ''), '/')); ?>" alt="" class="menu-edit-thumb" width="56" height="56">
                        <?php endif; ?>
                        <input type="file" name="image_boisson" class="file-input-btn-only file-input-btn-only--sm mt-1" accept="image/*">
                    </td>
                    <td><input type="text" name="nom_boisson" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['nom_boisson']); ?>"></td>
                    <td><input type="number" step="0.01" name="prix_unitaire" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($b['prix_unitaire'] ?? 0)); ?>"></td>
                    <td><?php dashboard_render_boisson_type_select('type_boisson', (string) ($b['type_boisson'] ?? ''), $typesBoissonOptions, true); ?></td>
                    <td><input type="text" name="dosage" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['dosage'] ?? ''); ?>"></td>
                    <td><input type="number" name="quantite_boisson" class="form-control form-control-sm" value="<?php echo (int) $b['quantite_boisson']; ?>"></td>
                    <td class="d-flex gap-1 flex-wrap">
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
