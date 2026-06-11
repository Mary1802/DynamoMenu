<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/employe_passwords.php';

$pdo = admin_init();
$message = '';
$error = '';
$roles = ['admin' => 'Administrateur', 'cuisinier' => 'Cuisinier', 'caissier' => 'Caissier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employe'])) {
        $email = trim($_POST['email_employe'] ?? '');
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');
        $role = (string) ($_POST['role'] ?? '');

        if ($email === '' || $mdp === '') {
            $error = 'Email et mot de passe requis.';
        } elseif (!employe_password_is_valid($mdp)) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif (!isset($roles[$role])) {
            $error = 'Rôle invalide.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO employe (nom_employe, prenom_employe, email_employe, mot_de_passe, role, telephone_employe) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    trim($_POST['nom_employe']),
                    trim($_POST['prenom_employe']),
                    $email,
                    password_hash_employe($mdp),
                    $role,
                    trim($_POST['telephone_employe'] ?? ''),
                ]);
                admin_log($pdo, 'employe_create', "Employé créé : {$email} ({$role})");
                $message = 'Compte créé. L\'employé peut se connecter avec son email et le mot de passe défini.';
            } catch (PDOException $e) {
                $error = 'Cet email est déjà utilisé ou la création a échoué.';
            }
        }
    }

    if (isset($_POST['reset_password'])) {
        $id = (int) ($_POST['id_employe'] ?? 0);
        $mdp = (string) ($_POST['nouveau_mot_de_passe'] ?? '');

        if ($id <= 0 || $mdp === '') {
            $error = 'Employé et nouveau mot de passe requis.';
        } elseif (!employe_password_is_valid($mdp)) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            $stmt = $pdo->prepare('SELECT email_employe FROM employe WHERE id_employe = ?');
            $stmt->execute([$id]);
            $email = $stmt->fetchColumn();
            if (!$email) {
                $error = 'Employé introuvable.';
            } else {
                $pdo->prepare('UPDATE employe SET mot_de_passe = ? WHERE id_employe = ?')
                    ->execute([password_hash_employe($mdp), $id]);
                admin_log($pdo, 'employe_password_reset', "Mot de passe réinitialisé : {$email}");
                $message = 'Mot de passe mis à jour pour ' . $email . '.';
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

$q = $_GET['q'] ?? '';
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM employe WHERE nom_employe LIKE ? OR prenom_employe LIKE ? OR email_employe LIKE ? ORDER BY role, nom_employe');
    $qpattern = '%' . $q . '%';
    $stmt->execute([$qpattern, $qpattern, $qpattern]);
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $employes = $pdo->query('SELECT * FROM employe ORDER BY role, nom_employe')->fetchAll(PDO::FETCH_ASSOC);
}

admin_shell_start(
    'Admin — Employés',
    'employes',
    'Équipe',
    'Comptes employés',
    'Créez les identifiants de connexion (email + mot de passe) pour chaque membre du staff.'
);
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par nom ou email..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($q !== ''): ?>
        <div class="col-md-2">
            <a href="employes.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Nouveau compte employé</div>
    <p class="text-secondary small mb-3">L'employé se connectera sur <strong>login.php</strong> avec l'email et le mot de passe que vous définissez ici. Le rôle détermine son accès (admin, cuisine ou caisse).</p>
    <form method="post" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small text-secondary">Nom</label>
            <input type="text" name="nom_employe" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Prénom</label>
            <input type="text" name="prenom_employe" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Email (identifiant)</label>
            <input type="email" name="email_employe" class="form-control" required autocomplete="off">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Mot de passe</label>
            <input type="password" name="mot_de_passe" class="form-control" minlength="6" required autocomplete="new-password">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Rôle</label>
            <select name="role" class="form-select" required>
                <?php foreach ($roles as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo $l; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" name="add_employe" class="btn-primary w-100" title="Créer le compte">+</button>
        </div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Liste des comptes</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Téléphone</th>
                    <th>Nouveau mot de passe</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employes as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['prenom_employe'] . ' ' . $e['nom_employe']); ?></td>
                    <td><?php echo htmlspecialchars($e['email_employe']); ?></td>
                    <td><?php echo htmlspecialchars($roles[$e['role']] ?? $e['role']); ?></td>
                    <td><?php echo htmlspecialchars($e['telephone_employe'] ?? '—'); ?></td>
                    <td>
                        <form method="post" class="d-flex gap-1 flex-wrap align-items-center">
                            <input type="hidden" name="id_employe" value="<?php echo (int) $e['id_employe']; ?>">
                            <input type="password" name="nouveau_mot_de_passe" class="form-control form-control-sm" style="min-width:140px;max-width:180px;" minlength="6" placeholder="Nouveau mot de passe" required autocomplete="new-password">
                            <button type="submit" name="reset_password" class="btn-details btn-sm">Mettre à jour</button>
                        </form>
                    </td>
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
