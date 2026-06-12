<?php

require_once __DIR__ . '/../includes/admin_layout.php';

use App\Controller\Admin\EmployeController;

admin_init();

$result = (new EmployeController())->handle($_GET, $_POST, $_SESSION);
$message = $result['message'];
$error = $result['error'];
$employes = $result['employes'];
$q = $result['q'];
$passwordHasher = $result['passwordHasher'];
$passwordService = $result['passwordService'];
$roles = EmployeController::ROLES;

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
    <p class="text-secondary small mb-3">Identifiants de connexion sur <strong>login.php</strong>. Le mot de passe affiché est celui défini à la création ou lors de la dernière réinitialisation.</p>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>E-mail (identifiant)</th>
                    <th>Mot de passe</th>
                    <th>Rôle</th>
                    <th>Téléphone</th>
                    <th>Nouveau mot de passe</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employes as $e):
                $visiblePassword = $passwordService->displayPassword(
                    $e->email,
                    $e->passwordNote,
                    $e->motDePasse
                ) ?? $e->visiblePassword($passwordHasher);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($e->fullName()); ?></td>
                    <td><span class="staff-credential"><?php echo htmlspecialchars($e->email); ?></span></td>
                    <td>
                        <?php if ($visiblePassword !== null): ?>
                        <span class="staff-credential staff-credential--password"><?php echo htmlspecialchars($visiblePassword); ?></span>
                        <?php else: ?>
                        <span class="text-secondary small">Définissez un mot de passe ci-contre</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($roles[$e->role] ?? $e->role); ?></td>
                    <td><?php echo htmlspecialchars($e->telephone ?? '—'); ?></td>
                    <td>
                        <form method="post" class="d-flex gap-1 flex-wrap align-items-center">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
                            <input type="password" name="nouveau_mot_de_passe" class="form-control form-control-sm" style="min-width:140px;max-width:180px;" minlength="6" placeholder="Nouveau mot de passe" required autocomplete="new-password">
                            <button type="submit" name="reset_password" class="btn-details btn-sm">Mettre à jour</button>
                        </form>
                    </td>
                    <td>
                        <?php if ($e->id !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
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
<style>
.staff-credential {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.9rem;
    color: var(--text-primary, #f8fafc);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 6px;
    padding: 0.2rem 0.5rem;
    user-select: all;
    word-break: break-all;
}
html.theme-light .staff-credential {
    color: #1a1a2e;
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.12);
}
.staff-credential--password {
    letter-spacing: 0.02em;
}
</style>
<?php admin_shell_end(); ?>
