<?php

declare(strict_types=1);

use App\Http\AdminPage;

AdminPage::shellStart(
    'Admin — Clients',
    'clients',
    'CRM',
    'Clients',
    'Consultez, modifiez ou supprimez les profils clients.'
);
?>
<?php if (!empty($message)): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="success-message" style="color:#dc3545;border-color:rgba(220,53,69,.3);"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par nom, email ou téléphone..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($q !== ''): ?>
        <div class="col-md-2">
            <a href="clients.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Clients (<?php echo count($clients); ?>)</div>
    <div class="table-responsive-wrap">
        <table class="data-table clients-admin-table">
            <thead>
                <tr>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-4">Aucun client trouvé.</td>
                </tr>
            <?php else: ?>
            <?php foreach ($clients as $cl): ?>
                <tr>
                    <td colspan="5" class="p-0">
                        <form method="post" class="clients-edit-form" id="edit-client-<?php echo (int) $cl['id_client']; ?>">
                            <input type="hidden" name="id_client" value="<?php echo (int) $cl['id_client']; ?>">
                            <div class="clients-edit-grid">
                                <input type="text" name="prenom_client" class="form-control form-control-sm" required
                                       value="<?php echo htmlspecialchars((string) ($cl['prenom_client'] ?? '')); ?>"
                                       placeholder="Prénom" aria-label="Prénom">
                                <input type="text" name="nom_client" class="form-control form-control-sm" required
                                       value="<?php echo htmlspecialchars((string) ($cl['nom_client'] ?? '')); ?>"
                                       placeholder="Nom" aria-label="Nom">
                                <input type="email" name="email_client" class="form-control form-control-sm" required
                                       value="<?php echo htmlspecialchars((string) ($cl['email_client'] ?? '')); ?>"
                                       placeholder="E-mail" aria-label="E-mail">
                                <input type="tel" name="telephone_client" class="form-control form-control-sm" required
                                       value="<?php echo htmlspecialchars((string) ($cl['telephone_client'] ?? '')); ?>"
                                       placeholder="Téléphone" aria-label="Téléphone"
                                       minlength="10" maxlength="13">
                                <span class="clients-date text-secondary small">
                                    <?php echo !empty($cl['date_inscription']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $cl['date_inscription']))) : '—'; ?>
                                </span>
                            </div>
                        </form>
                    </td>
                    <td class="clients-actions">
                        <button type="submit" form="edit-client-<?php echo (int) $cl['id_client']; ?>" name="update_client" class="btn-details btn-sm">Enregistrer</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce client ? Ses commandes resteront sans profil associé.');">
                            <input type="hidden" name="id_client" value="<?php echo (int) $cl['id_client']; ?>">
                            <button type="submit" name="delete_client" class="btn-details btn-sm staff-btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
.clients-admin-table td { vertical-align: middle; }
.clients-edit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.4fr 1.1fr 0.7fr;
    gap: 0.45rem;
    padding: 0.45rem 0.5rem;
    align-items: center;
}
.clients-actions {
    white-space: nowrap;
    text-align: right;
}
.clients-actions .btn-details { margin-left: 0.25rem; }
.staff-btn-danger {
    border-color: rgba(220, 53, 69, 0.45) !important;
    color: #f5a8b0 !important;
}
@media (max-width: 991.98px) {
    .clients-edit-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
<?php AdminPage::shellEnd(); ?>
