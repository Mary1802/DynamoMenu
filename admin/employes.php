<?php

require_once __DIR__ . '/../includes/admin_layout.php';

$pdo = admin_pdo();
$message = '';
$error = '';
$roles = ['admin' => 'Administrateur', 'cuisinier' => 'Cuisinier', 'caissier' => 'Caissier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employe'])) {
        $email = trim($_POST['email_employe'] ?? '');
        $mdp = $_POST['mot_de_passe'] ?? '';
        if ($email === '' || $mdp === '') {
            $error = 'Email et mot de passe requis.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO employe (nom_employe, prenom_employe, email_employe, mot_de_passe, role, telephone_employe) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    trim($_POST['nom_employe']),
                    trim($_POST['prenom_employe']),
                    $email,
                    $mdp,
                    $_POST['role'],
                    trim($_POST['telephone_employe'] ?? ''),
                ]);
                admin_log($pdo, 'employe_create', "Employé créé : {$email}");
                $message = 'Employé ajouté.';
            } catch (PDOException $e) {
                $error = 'Email déjà utilisé ou erreur SQL.';
            }
        }
    }
    if (isset($_POST['delete_employe'])) {
        $id = (int) $_POST['id_employe'];
        if ($id !== (int) ($_SESSION['user_id'] ?? 0)) {
            $pdo->prepare('DELETE FROM employe WHERE id_employe = ?')->execute([$id]);
            admin_log($pdo, 'employe_delete', "Employé #{$id} supprimé");
            $message = 'Employé supprimé.';
        } else {
            $error = 'Vous ne pouvez pas supprimer votre propre compte.';
        }
    }
}

$employes = $pdo->query('SELECT * FROM employe ORDER BY role, nom_employe')->fetchAll(PDO::FETCH_ASSOC);

admin_shell_start('Admin — Employés', 'employes', 'Équipe', 'Employés', 'Comptes staff (connexion via login.php).');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Nouvel employé</div>
    <form method="post" class="row g-3">
        <div class="col-md-2"><input type="text" name="nom_employe" class="form-control" placeholder="Nom" required></div>
        <div class="col-md-2"><input type="text" name="prenom_employe" class="form-control" placeholder="Prénom" required></div>
        <div class="col-md-3"><input type="email" name="email_employe" class="form-control" placeholder="Email" required></div>
        <div class="col-md-2"><input type="password" name="mot_de_passe" class="form-control" placeholder="Mot de passe" required></div>
        <div class="col-md-2">
            <select name="role" class="form-select" required>
                <?php foreach ($roles as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo $l; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><button type="submit" name="add_employe" class="btn-primary w-100">+</button></div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Liste</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Téléphone</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($employes as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['prenom_employe'] . ' ' . $e['nom_employe']); ?></td>
                    <td><?php echo htmlspecialchars($e['email_employe']); ?></td>
                    <td><?php echo htmlspecialchars($roles[$e['role']] ?? $e['role']); ?></td>
                    <td><?php echo htmlspecialchars($e['telephone_employe'] ?? '—'); ?></td>
                    <td>
                        <?php if ((int) $e['id_employe'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id_employe" value="<?php echo (int) $e['id_employe']; ?>">
                            <button type="submit" name="delete_employe" class="btn-details btn-sm" onclick="return confirm('Supprimer cet employé ?');">Supprimer</button>
                        </form>
                        <?php else: ?>
                        <span class="text-secondary small">(vous)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
