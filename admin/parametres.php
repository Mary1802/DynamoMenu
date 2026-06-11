<?php



require_once __DIR__ . '/../includes/admin_layout.php';

require_once __DIR__ . '/../includes/schema_upgrade.php';



$user = staff_user();

$pdo = admin_init();

schema_upgrade($pdo);



$message = '';

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_contact'])) {

        $nom = trim($_POST['nom'] ?? '');

        $adresse = trim($_POST['adresse'] ?? '');

        $telephone = trim($_POST['telephone'] ?? '');

        $whatsapp = trim($_POST['whatsapp'] ?? '');

        $email = trim($_POST['email'] ?? '');

        $horaires = trim($_POST['horaires'] ?? '');

        $actif = 1;



        if ($nom === '') {

            $message = 'Le nom est requis.';

        } else {

            if (!empty($_POST['id_contact'])) {

                $stmt = $pdo->prepare('UPDATE contact SET nom = ?, adresse = ?, telephone = ?, whatsapp = ?, email = ?, horaires = ?, actif = ? WHERE id_contact = ?');

                $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $horaires, $actif, (int) $_POST['id_contact']]);

                $message = 'Contact mis à jour.';

                $editingId = (int) $_POST['id_contact'];

            } else {

                $stmt = $pdo->prepare('INSERT INTO contact (nom, adresse, telephone, whatsapp, email, horaires, actif) VALUES (?, ?, ?, ?, ?, ?, ?)');

                $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $horaires, $actif]);

                $message = 'Contact ajouté.';

                $editingId = 0;

            }

        }

    }



    if (isset($_POST['delete_contact']) && !empty($_POST['id_contact'])) {

        $stmt = $pdo->prepare('DELETE FROM contact WHERE id_contact = ?');

        $stmt->execute([(int) $_POST['id_contact']]);

        $message = 'Contact supprimé.';

        $editingId = 0;

    }

}



$contactList = dashboard_contact_list($pdo);

$currentContact = null;

if ($editingId > 0) {

    foreach ($contactList as $item) {

        if ((int) $item['id_contact'] === $editingId) {

            $currentContact = $item;

            break;

        }

    }

}



try {

    $account = dashboard_staff_account($pdo, $user);

} catch (PDOException $e) {

    $account = [

        'nom' => $user['nom'] ?? 'Utilisateur',

        'prenom' => '',

        'nom_famille' => '',

        'email' => $user['email'] ?? '',

        'role' => staff_role_label((string) ($user['role'] ?? 'admin')),

    ];

}



admin_shell_start(

    'Admin — Paramètres',

    'parametres',

    'Administration',

    'Paramètres',

    'Thème, compte et coordonnées affichées aux équipes et sur le site client.'

);

?>



<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>



<div class="dashboard-card settings-single-card">

    <section class="settings-panel-section">

        <h3 class="settings-panel-title">Thème d'affichage</h3>

        <p class="text-secondary small mb-2">Clair : fond blanc. Sombre : fond noir.</p>

        <div class="theme-switcher" role="group" aria-label="Thème">

            <button type="button" class="theme-switch-btn" data-theme-set="dark"><i class="bi bi-moon-stars" aria-hidden="true"></i> Sombre</button>

            <button type="button" class="theme-switch-btn" data-theme-set="light"><i class="bi bi-sun" aria-hidden="true"></i> Clair</button>

        </div>

    </section>



    <section class="settings-panel-section">

        <h3 class="settings-panel-title">Mon compte</h3>

        <dl class="account-dl">

            <dt>Nom complet</dt>

            <dd><?php echo htmlspecialchars($account['nom'] ?? ''); ?></dd>

            <dt>E-mail</dt>

            <dd><?php echo htmlspecialchars($account['email'] ?? '—'); ?></dd>

            <dt>Rôle</dt>

            <dd><?php echo htmlspecialchars($account['role'] ?? ''); ?></dd>

        </dl>

        <p class="text-secondary small mb-0">Le mot de passe n'est pas affiché ici.</p>

    </section>

    <section id="contacts-admin" class="settings-panel-section">

        <h3 class="settings-panel-title"><?php echo $currentContact ? 'Modifier un contact' : 'Ajouter un contact'; ?></h3>

        <p class="text-secondary small mb-3">Coordonnées affichées sur l'accueil client et dans les paramètres caisse / cuisine.</p>

        <form method="post" class="row g-3">

            <input type="hidden" name="id_contact" value="<?php echo htmlspecialchars($currentContact['id_contact'] ?? ''); ?>">

            <div class="col-md-6">

                <label class="form-label text-secondary" for="contactNom">Nom</label>

                <input type="text" name="nom" id="contactNom" class="form-control" required value="<?php echo htmlspecialchars($currentContact['nom'] ?? ''); ?>">

            </div>

            <div class="col-md-6">

                <label class="form-label text-secondary" for="contactEmail">Email</label>

                <input type="email" name="email" id="contactEmail" class="form-control" value="<?php echo htmlspecialchars($currentContact['email'] ?? ''); ?>">

            </div>

            <div class="col-md-6">

                <label class="form-label text-secondary" for="contactTel">Téléphone</label>

                <input type="text" name="telephone" id="contactTel" class="form-control" value="<?php echo htmlspecialchars($currentContact['telephone'] ?? ''); ?>">

            </div>

            <div class="col-md-6">

                <label class="form-label text-secondary" for="contactWhatsapp">WhatsApp</label>

                <input type="text" name="whatsapp" id="contactWhatsapp" class="form-control" value="<?php echo htmlspecialchars($currentContact['whatsapp'] ?? ''); ?>">

            </div>

            <div class="col-12">

                <label class="form-label text-secondary" for="contactAdresse">Adresse</label>

                <input type="text" name="adresse" id="contactAdresse" class="form-control" value="<?php echo htmlspecialchars($currentContact['adresse'] ?? ''); ?>">

            </div>

            <div class="col-12">

                <label class="form-label text-secondary" for="contactHoraires">Horaires</label>

                <input type="text" name="horaires" id="contactHoraires" class="form-control" placeholder="Lun–Dim : 11h00 – 23h00" value="<?php echo htmlspecialchars($currentContact['horaires'] ?? ''); ?>">

            </div>

            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">

                <button type="submit" name="save_contact" class="btn-primary">Enregistrer</button>

                <?php if ($currentContact): ?>

                <button type="submit" name="delete_contact" class="btn btn-danger" onclick="return confirm('Supprimer ce contact ?');">Supprimer</button>

                <a href="parametres.php#contacts-admin" class="btn-details btn-sm">Annuler</a>

                <?php endif; ?>

            </div>

        </form>

    </section>

    <section class="settings-panel-section">

        <h3 class="settings-panel-title">Contacts enregistrés</h3>

        <div class="table-responsive-wrap">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Nom</th>

                        <th>Email</th>

                        <th>Téléphone</th>

                        <th>WhatsApp</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($contactList)): ?>

                    <tr><td colspan="6">Aucun contact enregistré.</td></tr>

                <?php else: foreach ($contactList as $item): ?>

                    <tr>

                        <td><?php echo (int) $item['id_contact']; ?></td>

                        <td><?php echo htmlspecialchars($item['nom']); ?></td>

                        <td><?php echo htmlspecialchars($item['email'] ?? ''); ?></td>

                        <td><?php echo htmlspecialchars($item['telephone'] ?? ''); ?></td>

                        <td><?php echo htmlspecialchars($item['whatsapp'] ?? ''); ?></td>

                        <td>

                            <a href="parametres.php?edit=<?php echo (int) $item['id_contact']; ?>#contacts-admin" class="btn-details btn-sm">Modifier</a>

                        </td>

                    </tr>

                <?php endforeach; endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</div>



<?php admin_shell_end(); ?>

