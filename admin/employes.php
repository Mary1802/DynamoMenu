<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Controller\Admin\EmployeController;
use App\Http\AdminPage;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
$roles = EmployeController::ROLES;

AdminPage::shellStart(
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
    <p class="text-secondary small mb-3">L'employé se connectera sur <strong>login.php</strong> avec l'email et le mot de passe que vous définissez ici, en choisissant le <strong>même rôle</strong> dans la liste (admin, manager, cuisine ou caisse).</p>
    <form method="post" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small text-secondary">Nom</label>
            <input type="text" name="nom_employe" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Prénom</label>
            <input type="text" name="prenom_employe" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Email (identifiant)</label>
            <input type="email" name="email_employe" class="form-control" required autocomplete="off">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Téléphone</label>
            <input type="tel" name="telephone_employe" class="form-control" placeholder="Optionnel" autocomplete="tel">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Mot de passe</label>
            <input type="password" name="mot_de_passe" class="form-control" minlength="6" required autocomplete="new-password">
        </div>
        <div class="col-md-1">
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
    <div class="chart-title">Liste des comptes (<?php echo count($employes); ?>)</div>
    <p class="text-secondary small mb-3">Identifiants de connexion sur <strong>login.php</strong>. Le mot de passe affiché est celui défini à la création ou lors de la dernière réinitialisation.</p>
    <div class="table-responsive-wrap">
        <table class="data-table staff-accounts-table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Email</th>
                    <th>Mot de passe</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Nouveau mot de passe</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($employes === []): ?>
                <tr>
                    <td colspan="7" class="text-center text-secondary py-4">Aucun compte employé.</td>
                </tr>
            <?php else: ?>
            <?php foreach ($employes as $e):
                $visiblePassword = $passwordService->displayPassword(
                    $e->email,
                    $e->passwordNote,
                    $e->motDePasse
                ) ?? $e->visiblePassword($passwordHasher);
                $roleInvalid = !isset($roles[$e->role]);
                ?>
                <tr>
                    <td class="staff-col-name"><?php echo htmlspecialchars($e->fullName()); ?></td>
                    <td><span class="staff-credential"><?php echo htmlspecialchars($e->email); ?></span></td>
                    <td>
                        <?php if ($visiblePassword !== null): ?>
                        <span class="staff-credential staff-credential--password"><?php echo htmlspecialchars($visiblePassword); ?></span>
                        <?php else: ?>
                        <span class="text-secondary small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="staff-col-phone">
                        <form method="post" class="staff-row-form">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
                            <input type="tel" name="telephone_employe" class="form-control form-control-sm staff-phone-input" value="<?php echo htmlspecialchars($e->telephone ?? ''); ?>" placeholder="—" autocomplete="tel" aria-label="Téléphone de <?php echo htmlspecialchars($e->fullName()); ?>">
                            <button type="submit" name="update_telephone" class="btn-details btn-sm">OK</button>
                        </form>
                    </td>
                    <td class="staff-col-role">
                        <?php if ($roleInvalid): ?>
                        <span class="text-warning small d-block mb-1">Rôle invalide</span>
                        <?php endif; ?>
                        <form method="post" class="staff-row-form">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
                            <select name="role" class="form-select form-select-sm staff-role-select" aria-label="Rôle de <?php echo htmlspecialchars($e->fullName()); ?>">
                                <?php foreach ($roles as $k => $l): ?>
                                <option value="<?php echo $k; ?>"<?php echo $e->role === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_role" class="btn-details btn-sm">OK</button>
                        </form>
                    </td>
                    <td class="staff-col-password">
                        <form method="post" class="staff-row-form">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
                            <input type="password" name="nouveau_mot_de_passe" class="form-control form-control-sm staff-password-input" minlength="6" placeholder="Nouveau mot de passe" required autocomplete="new-password" aria-label="Nouveau mot de passe pour <?php echo htmlspecialchars($e->fullName()); ?>">
                            <button type="submit" name="reset_password" class="btn-details btn-sm">Mettre à jour</button>
                        </form>
                    </td>
                    <td class="staff-col-actions">
                        <?php if ($e->id !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <form method="post" class="staff-row-form staff-row-form--solo">
                            <input type="hidden" name="id_employe" value="<?php echo $e->id; ?>">
                            <button type="submit" name="delete_employe" class="btn-details btn-sm staff-btn-danger" onclick="return confirm('Supprimer cet employé ?');">Supprimer</button>
                        </form>
                        <?php else: ?>
                        <span class="text-secondary small">(vous)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
.staff-accounts-table th,
.staff-accounts-table td {
    vertical-align: middle;
}

.staff-col-name {
    font-weight: 600;
    color: var(--text-primary, #f8fafc);
    white-space: nowrap;
}

.staff-col-phone {
    min-width: 10rem;
}

.staff-col-role,
.staff-col-password {
    min-width: 11rem;
}

.staff-col-actions {
    white-space: nowrap;
    text-align: right;
}

.staff-credential {
    display: inline-block;
    max-width: 14rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.82rem;
    line-height: 1.3;
    color: var(--text-primary, #f8fafc);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 6px;
    padding: 0.2rem 0.5rem;
    user-select: all;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

html.theme-light .staff-credential {
    color: #1a1a2e;
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.12);
}

.staff-credential--password {
    letter-spacing: 0.03em;
}

.staff-row-form {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
    min-width: 0;
}

.staff-row-form--solo {
    justify-content: flex-end;
}

.staff-role-select {
    width: 7.5rem;
    min-width: 7rem;
    flex: 0 0 auto;
}

.staff-phone-input {
    width: 8.5rem;
    min-width: 7rem;
    flex: 1 1 auto;
    max-width: 10rem;
}

.staff-password-input {
    width: 10rem;
    min-width: 8rem;
    flex: 1 1 auto;
    max-width: 12rem;
}

.staff-row-form .btn-details {
    flex-shrink: 0;
    white-space: nowrap;
}

.staff-btn-danger {
    color: #f8a4b0;
    border-color: rgba(220, 53, 69, 0.35);
}

.staff-btn-danger:hover {
    color: #fff;
    background: rgba(220, 53, 69, 0.2);
    border-color: rgba(220, 53, 69, 0.5);
}
</style>
<?php AdminPage::shellEnd(); ?>
