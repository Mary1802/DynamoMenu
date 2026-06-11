<?php
/**
 * Helpers partagés pour les dashboards employés
 */

/**
 * Liens CSS/JS communs (chemins relatifs depuis admin/, cuisine/ ou caissier/)
 */
function dashboard_asset_links(string $pageTitle): void
{
    $title = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboards.css?v=26">
    <script>try{var _t=localStorage.getItem('dm_dashboard_theme');if(_t==='light')document.documentElement.classList.add('theme-light');}catch(e){}</script>
HTML;
    require_once __DIR__ . '/staff_auth.php';
    staff_session_start();
    csrf_meta_tag();
}

function dashboard_scripts(): void
{
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="../assets/js/theme.js?v=1"></script>';
    echo '<script src="../assets/js/csrf.js?v=1"></script>';
    echo '<script src="../assets/js/dashboard.js?v=8"></script>';
}

/**
 * Pied de sidebar : utilisateur + déconnexion.
 *
 * @param 'admin'|'cuisinier'|'caissier' $context
 */
function dashboard_sidebar_user_footer(string $context): void
{
    require_once __DIR__ . '/staff_auth.php';
    $user = staff_user();
    $nom = (string) ($user['nom'] ?? $_SESSION['nom'] ?? 'Utilisateur');
    $roleLabel = staff_role_label((string) ($user['role'] ?? $context));
    $logoutHref = '../logout.php';
    ?>
    <div class="user-info">
        <div class="user-avatar"><?php echo strtoupper(substr($nom, 0, 1)); ?></div>
        <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($nom); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($roleLabel); ?></div>
        </div>
    </div>
    <a href="<?php echo htmlspecialchars($logoutHref); ?>" class="sidebar-logout-btn">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        <span>Déconnexion</span>
    </a>
    <?php
}

/**
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_demandes_paiement(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'demande_paiement'");
        if ($stmt->fetchColumn() === false) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT 
                d.*,
                c.montant_total,
                c.date_commande,
                t.num_table,
                cl.nom_client,
                cl.prenom_client,
                cl.telephone_client
            FROM demande_paiement d
            JOIN commande c ON d.num_commande = c.num_commande
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE d.statut = 'en_attente'
            ORDER BY d.date_demande ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function dashboard_order_items_list(?string $detailsPlats): array
{
    if ($detailsPlats === null || $detailsPlats === '') {
        return [];
    }

    return array_values(array_filter(
        array_map('trim', explode(', ', $detailsPlats)),
        static fn(string $item): bool => $item !== ''
    ));
}

/** @return array<string, string>|array<string, mixed> */
function dashboard_contacts(?PDO $pdo = null): array
{
    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'contact'");
            if ($stmt && $stmt->fetchColumn() !== false) {
                $stmt = $pdo->prepare('SELECT * FROM contact ORDER BY id_contact ASC LIMIT 1');
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row !== false) {
                    return $row;
                }
            }
        } catch (PDOException $e) {
            // fallback to config file
        }
    }

    $app = is_file(dirname(__DIR__) . '/config/app.php')
        ? require dirname(__DIR__) . '/config/app.php'
        : [];

    return is_array($app['contacts'] ?? null) ? $app['contacts'] : [];
}

/**
 * @return list<array<string, mixed>>
 */
function dashboard_contact_list(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'contact'");
        if ($stmt && $stmt->fetchColumn() !== false) {
            $stmt = $pdo->query('SELECT * FROM contact ORDER BY id_contact ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // fallback to config file
    }

    $single = dashboard_contacts();
    return $single ? [$single] : [];
}

/**
 * Lignes détaillées d'une commande (plats / boissons).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_order_lines(PDO $pdo, int $numCommande): array
{
    require_once __DIR__ . '/money.php';
    contient_ensure_schema($pdo);

    $stmt = $pdo->prepare("
        SELECT
            d.quantite,
            d.prix,
            d.sous_total,
            d.sauces,
            d.personnalisation_boisson,
            p.nom_plat,
            b.nom_boisson
        FROM contient d
        LEFT JOIN plat p ON d.id_plat = p.id_plat
        LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
        WHERE d.num_commande = ?
        ORDER BY d.id_detail
    ");
    $stmt->execute([$numCommande]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dashboard_line_label(array $line): string
{
    if (!empty($line['nom_plat'])) {
        $label = (string) $line['nom_plat'];
        if (!empty($line['sauces'])) {
            $label .= ' — Sauces: ' . $line['sauces'];
        }

        return $label;
    }

    $label = (string) ($line['nom_boisson'] ?? 'Article');
    if (!empty($line['personnalisation_boisson'])) {
        $label .= ' — ' . $line['personnalisation_boisson'];
    }

    return $label;
}

/** Chaîne pour filtre recherche (nom, prénom, tél., table, n° commande, articles). */
function dashboard_order_search_blob(array $row): string
{
    $parts = [
        $row['num_commande'] ?? '',
        $row['nom_client'] ?? '',
        $row['prenom_client'] ?? '',
        $row['telephone_client'] ?? '',
        $row['num_table'] ?? '',
        $row['details_plats'] ?? '',
        $row['details_search'] ?? '',
        $row['instructions_speciales'] ?? '',
    ];

    if (!empty($row['lignes']) && is_array($row['lignes'])) {
        foreach ($row['lignes'] as $line) {
            $parts[] = dashboard_line_label($line);
            if (!empty($line['sauces'])) {
                $parts[] = $line['sauces'];
            }
            if (!empty($line['personnalisation_boisson'])) {
                $parts[] = $line['personnalisation_boisson'];
            }
        }
    }

    return mb_strtolower(implode(' ', array_filter(array_map('strval', $parts))));
}

/**
 * @param list<array<string, mixed>> $orders
 */
function dashboard_attach_order_lines(PDO $pdo, array &$orders): void
{
    foreach ($orders as &$order) {
        $num = (int) ($order['num_commande'] ?? 0);
        if ($num <= 0) {
            $order['lignes'] = [];
            continue;
        }
        $order['lignes'] = dashboard_fetch_order_lines($pdo, $num);
        $order['details_search'] = implode(' ', array_map(
            static fn(array $l): string => dashboard_line_label($l),
            $order['lignes']
        ));
    }
    unset($order);
}

/**
 * @param list<array<string, mixed>> $lignes
 */
function dashboard_render_kitchen_order_details(array $lignes): void
{
    if ($lignes === []) {
        echo '<p class="kitchen-lines-empty text-secondary small mb-0">Aucun détail article.</p>';

        return;
    }
    ?>
    <ul class="kitchen-lines-list">
        <?php foreach ($lignes as $line):
            $isPlat = !empty($line['nom_plat']);
            $nom = $isPlat ? (string) $line['nom_plat'] : (string) ($line['nom_boisson'] ?? 'Article');
            ?>
        <li class="kitchen-line <?php echo $isPlat ? 'kitchen-line--plat' : 'kitchen-line--boisson'; ?>">
            <span class="kitchen-line-qty">×<?php echo (int) $line['quantite']; ?></span>
            <div class="kitchen-line-body">
                <span class="kitchen-line-name"><?php echo htmlspecialchars($nom); ?></span>
                <?php if ($isPlat && !empty($line['sauces'])): ?>
                <span class="kitchen-line-extra"><i class="bi bi-droplet" aria-hidden="true"></i> Sauces : <strong><?php echo htmlspecialchars((string) $line['sauces']); ?></strong></span>
                <?php endif; ?>
                <?php if (!empty($line['personnalisation_boisson'])): ?>
                <span class="kitchen-line-extra"><i class="bi bi-cup-straw" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $line['personnalisation_boisson']); ?></span>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

/** Instructions spéciales saisies par le client (allergies, préférences, etc.). */
function dashboard_render_kitchen_instructions(?string $instructions): void
{
    $instructions = trim((string) ($instructions ?? ''));
    if ($instructions === '') {
        return;
    }
    ?>
    <div class="kitchen-instructions" role="note">
        <div class="kitchen-instructions-label">
            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
            Instructions client
        </div>
        <p class="kitchen-instructions-text"><?php echo nl2br(htmlspecialchars($instructions)); ?></p>
    </div>
    <?php
}

/**
 * Détail complet d'une commande (page cuisine / commandes récentes).
 *
 * @param array<string, mixed> $commande
 * @param array<string, string> $statutLabels
 */
function dashboard_render_cuisine_commande_full_detail(array $commande, array $statutLabels): void
{
    require_once __DIR__ . '/money.php';
    $statut = $statutLabels[$commande['statut'] ?? ''] ?? ($commande['statut'] ?? '');
    ?>
    <div class="commande-detail-panel-inner">
        <div class="commande-detail-meta">
            <div><span class="text-secondary">N° commande</span><br><strong>#<?php echo str_pad((string) ($commande['num_commande'] ?? ''), 5, '0', STR_PAD_LEFT); ?></strong></div>
            <div><span class="text-secondary">Table</span><br><strong><?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></strong></div>
            <div><span class="text-secondary">Statut</span><br><strong><?php echo htmlspecialchars($statut); ?></strong></div>
            <div><span class="text-secondary">Date</span><br><strong><?php echo !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime($commande['date_commande'])) : '—'; ?></strong></div>
        </div>
        <div class="commande-detail-client mt-2">
            <span class="text-secondary">Client</span><br>
            <strong><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '—'))); ?></strong>
            <?php if (!empty($commande['telephone_client'])): ?>
            <br><span class="text-secondary">Tél.</span> <?php echo htmlspecialchars((string) $commande['telephone_client']); ?>
            <?php endif; ?>
        </div>
        <?php dashboard_render_kitchen_instructions($commande['instructions_speciales'] ?? null); ?>
        <div class="mt-3">
            <div class="settings-panel-title mb-2">Articles à préparer</div>
            <div class="order-items kitchen-order-items">
                <?php dashboard_render_kitchen_order_details($commande['lignes'] ?? []); ?>
            </div>
        </div>
        <?php if (isset($commande['montant_total'])): ?>
        <div class="commande-detail-total mt-2">
            <span>Total</span>
            <strong><?php echo format_money((float) $commande['montant_total']); ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Détail complet commande — page caisse.
 *
 * @param array<string, mixed> $commande
 * @param array<string, string> $statutLabels
 */
function dashboard_render_caissier_commande_detail(array $commande, array $statutLabels): void
{
    require_once __DIR__ . '/money.php';
    $statut = $statutLabels[$commande['statut'] ?? ''] ?? ($commande['statut'] ?? '');
    ?>
    <div class="commande-detail-panel-inner">
        <div class="commande-detail-meta">
            <div><span class="text-secondary">N° commande</span><br><strong>#<?php echo str_pad((string) ($commande['num_commande'] ?? ''), 5, '0', STR_PAD_LEFT); ?></strong></div>
            <div><span class="text-secondary">Table</span><br><strong><?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></strong></div>
            <div><span class="text-secondary">Statut</span><br><strong><?php echo htmlspecialchars($statut); ?></strong></div>
            <div><span class="text-secondary">Date commande</span><br><strong><?php echo !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime($commande['date_commande'])) : '—'; ?></strong></div>
        </div>
        <div class="commande-detail-client mt-2">
            <span class="text-secondary">Client</span><br>
            <strong><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '—'))); ?></strong>
            <?php if (!empty($commande['telephone_client'])): ?>
            <br><span class="text-secondary">Tél.</span> <?php echo htmlspecialchars((string) $commande['telephone_client']); ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($commande['num_facture'])): ?>
        <div class="commande-detail-paiement mt-2">
            <div class="settings-panel-title mb-1">Paiement enregistré</div>
            <div class="commande-detail-meta">
                <div><span class="text-secondary">Facture</span><br><strong>#<?php echo str_pad((string) $commande['num_facture'], 4, '0', STR_PAD_LEFT); ?></strong></div>
                <div><span class="text-secondary">Mode</span><br><strong><?php echo htmlspecialchars(dashboard_mode_paiement_label((string) ($commande['mode_paiement'] ?? 'especes'))); ?></strong></div>
                <div><span class="text-secondary">Payé le</span><br><strong><?php echo !empty($commande['date_paiement']) ? date('d/m/Y H:i', strtotime($commande['date_paiement'])) : '—'; ?></strong></div>
                <div><span class="text-secondary">Montant</span><br><strong><?php echo format_money((float) ($commande['total_paye'] ?? $commande['montant_total'] ?? 0)); ?></strong></div>
            </div>
            <a href="generer_facture.php?facture=<?php echo (int) $commande['num_facture']; ?>" target="_blank" rel="noopener" class="btn-details btn-sm mt-2 d-inline-block">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Voir facture
            </a>
        </div>
        <?php elseif (!empty($commande['mode_paiement_souhaite'])): ?>
        <div class="commande-detail-paiement mt-2">
            <span class="text-secondary">Paiement souhaité :</span>
            <strong><?php echo $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Cash'; ?></strong>
        </div>
        <?php endif; ?>
        <?php dashboard_render_kitchen_instructions($commande['instructions_speciales'] ?? null); ?>
        <div class="mt-3">
            <div class="settings-panel-title mb-2">Articles commandés</div>
            <div class="order-items kitchen-order-items">
                <?php dashboard_render_kitchen_order_details($commande['lignes'] ?? []); ?>
            </div>
        </div>
        <div class="commande-detail-total mt-2">
            <span>Total commande</span>
            <strong><?php echo format_money((float) ($commande['montant_total'] ?? 0)); ?></strong>
        </div>
    </div>
    <?php
}

/** Infos employé connecté (sans mot de passe). */
function dashboard_staff_account(PDO $pdo, ?array $user): array
{
    $fallback = [
        'nom' => $user['nom'] ?? 'Utilisateur',
        'email' => $user['email'] ?? '',
        'role' => staff_role_label((string) ($user['role'] ?? '')),
        'prenom' => '',
        'nom_famille' => '',
    ];

    $id = (int) ($user['user_id'] ?? 0);
    if ($id <= 0) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare('SELECT nom_employe, prenom_employe, email_employe, role FROM employe WHERE id_employe = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'nom' => trim(($row['prenom_employe'] ?? '') . ' ' . ($row['nom_employe'] ?? '')),
                'prenom' => (string) ($row['prenom_employe'] ?? ''),
                'nom_famille' => (string) ($row['nom_employe'] ?? ''),
                'email' => (string) ($row['email_employe'] ?? ''),
                'role' => staff_role_label((string) ($row['role'] ?? $user['role'] ?? '')),
            ];
        }
    } catch (PDOException $e) {
        // table absente
    }

    return $fallback;
}

/**
 * @return array{nb:int,ca:float,ca_especes:float,ca_mobile:float,ca_carte:float}
 */
function dashboard_sales_totals(PDO $pdo, string $scope, string $value): array
{
    $empty = ['nb' => 0, 'ca' => 0.0, 'ca_especes' => 0.0, 'ca_mobile' => 0.0, 'ca_carte' => 0.0];

    if ($scope === 'day') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS nb,
                   COALESCE(SUM(total_paye), 0) AS ca,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN total_paye ELSE 0 END), 0) AS ca_especes,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN total_paye ELSE 0 END), 0) AS ca_mobile,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN total_paye ELSE 0 END), 0) AS ca_carte
            FROM facture
            WHERE DATE(date_facture) = ?
        ");
        $stmt->execute([$value]);
    } elseif ($scope === 'month') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS nb,
                   COALESCE(SUM(total_paye), 0) AS ca,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN total_paye ELSE 0 END), 0) AS ca_especes,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN total_paye ELSE 0 END), 0) AS ca_mobile,
                   COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN total_paye ELSE 0 END), 0) AS ca_carte
            FROM facture
            WHERE DATE_FORMAT(date_facture, '%Y-%m') = ?
        ");
        $stmt->execute([$value]);
    } else {
        return $empty;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'nb' => (int) ($row['nb'] ?? 0),
        'ca' => (float) ($row['ca'] ?? 0),
        'ca_especes' => (float) ($row['ca_especes'] ?? 0),
        'ca_mobile' => (float) ($row['ca_mobile'] ?? 0),
        'ca_carte' => (float) ($row['ca_carte'] ?? 0),
    ];
}

function dashboard_report_month_key(int $annee, int $mois): string
{
    return sprintf('%04d-%02d', $annee, max(1, min(12, $mois)));
}

/** @return array{annee:int,mois:int,mois_key:string} */
function dashboard_report_parse_period(?int $annee, ?int $mois): array
{
    $annee = $annee ?? (int) date('Y');
    $mois = $mois ?? (int) date('n');
    $annee = max(2020, min(2100, $annee));
    $mois = max(1, min(12, $mois));

    return [
        'annee' => $annee,
        'mois' => $mois,
        'mois_key' => dashboard_report_month_key($annee, $mois),
    ];
}

function dashboard_report_month_label(int $annee, int $mois): string
{
    $noms = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    return ($noms[$mois] ?? '') . ' ' . $annee;
}

/**
 * Catégories de plats pour l’admin (menu carte + valeurs déjà en base).
 *
 * @return list<string>
 */
function dashboard_plat_categories(PDO $pdo): array
{
    $defaults = [
        'Apéritifs',
        'Entrées',
        'Plats principaux',
        'Combo',
        'Accompagnements',
        'Desserts',
        'Boissons',
    ];

    $fromDb = [];
    try {
        $stmt = $pdo->query(
            "SELECT DISTINCT TRIM(categorie) AS categorie FROM plat
             WHERE categorie IS NOT NULL AND TRIM(categorie) <> ''
             ORDER BY categorie"
        );
        $fromDb = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (PDOException $e) {
        // table absente ou erreur : défauts uniquement
    }

    $merged = [];
    foreach (array_merge($defaults, $fromDb) as $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }
        $merged[$label] = true;
    }

    $list = array_keys($merged);
    natcasesort($list);

    return array_values($list);
}

/**
 * Liste déroulante catégorie plat (admin menu).
 *
 * @param list<string> $categories
 */
function dashboard_render_plat_categorie_select(
    string $name,
    string $selected,
    array $categories,
    bool $small = false,
    bool $required = false
): void {
    $selected = trim($selected);
    $options = $categories;
    if ($selected !== '' && !in_array($selected, $options, true)) {
        $options[] = $selected;
        natcasesort($options);
        $options = array_values($options);
    }

    $class = $small ? 'form-select form-select-sm' : 'form-select';
    $req = $required ? ' required' : '';
    echo '<select name="' . htmlspecialchars($name) . '" class="' . $class . '" aria-label="Catégorie"' . $req . '>';
    echo '<option value=""' . ($selected === '' ? ' selected' : '') . '>— Catégorie —</option>';
    foreach ($options as $cat) {
        $isSelected = strcasecmp($selected, $cat) === 0;
        echo '<option value="' . htmlspecialchars($cat) . '"' . ($isSelected ? ' selected' : '') . '>';
        echo htmlspecialchars($cat) . '</option>';
    }
    echo '</select>';
}

/**
 * Types de boisson présents en base (table type_boisson).
 *
 * @return list<string>
 */
function dashboard_boisson_types(PDO $pdo): array
{
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'type_boisson'");
        if ($check === false || $check->fetchColumn() === false) {
            return [];
        }
        $stmt = $pdo->query('SELECT nom_type FROM type_boisson ORDER BY nom_type ASC');

        return array_values(array_filter(
            array_map(static fn ($row): string => trim((string) $row), $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []),
            static fn (string $name): bool => $name !== ''
        ));
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Liste déroulante type de boisson (admin menu).
 *
 * @param list<string> $types
 */
function dashboard_render_boisson_type_select(
    string $name,
    string $selected,
    array $types,
    bool $small = false,
    bool $required = false
): void {
    $selected = trim($selected);
    $options = $types;
    if ($selected !== '' && !in_array($selected, $options, true)) {
        $options[] = $selected;
        natcasesort($options);
        $options = array_values($options);
    }

    $class = $small ? 'form-select form-select-sm' : 'form-select';
    $req = $required ? ' required' : '';
    $disabled = $options === [] ? ' disabled' : '';
    echo '<select name="' . htmlspecialchars($name) . '" class="' . $class . '" aria-label="Type de boisson"' . $req . $disabled . '>';
    echo '<option value=""' . ($selected === '' ? ' selected' : '') . '>— Type —</option>';
    foreach ($options as $type) {
        $isSelected = strcasecmp($selected, $type) === 0;
        echo '<option value="' . htmlspecialchars($type) . '"' . ($isSelected ? ' selected' : '') . '>';
        echo htmlspecialchars($type) . '</option>';
    }
    echo '</select>';
}

/**
 * Lignes factures pour rapport caisse (mois entier ou un jour).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_factures_lignes(PDO $pdo, string $monthYm, ?string $dayYmd = null): array
{
    $baseSql = "
        SELECT f.*, c.num_commande, c.num_table, cl.nom_client, cl.prenom_client
        FROM facture f
        JOIN commande c ON f.num_commande = c.num_commande
        LEFT JOIN client cl ON c.id_client = cl.id_client
    ";

    if ($dayYmd !== null && $dayYmd !== '') {
        $stmt = $pdo->prepare($baseSql . ' WHERE DATE(f.date_facture) = ? ORDER BY f.date_facture ASC');
        $stmt->execute([$dayYmd]);
    } else {
        $stmt = $pdo->prepare($baseSql . " WHERE DATE_FORMAT(f.date_facture, '%Y-%m') = ? ORDER BY f.date_facture ASC");
        $stmt->execute([$monthYm]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Notifications cuisine / caisse (commandes actives).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_staff_notifications(PDO $pdo, string $role): array
{
    if ($role === 'cuisinier') {
        $stmt = $pdo->query("
            SELECT c.num_commande, c.statut, c.num_table, cl.nom_client, cl.prenom_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.statut = 'en_attente'
            ORDER BY c.date_commande ASC
            LIMIT 20
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'caissier') {
        $items = [];
        foreach (dashboard_fetch_demandes_paiement($pdo) as $d) {
            $items[] = [
                'type' => 'demande',
                'num_commande' => $d['num_commande'],
                'label' => 'Demande paiement table ' . ($d['num_table'] ?? '?'),
                'href' => 'paiement.php?voir_commande=' . (int) $d['num_commande'],
            ];
        }
        $stmt = $pdo->query("
            SELECT c.num_commande, c.num_table, cl.nom_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.statut = 'livree'
              AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
            ORDER BY c.date_commande ASC
            LIMIT 8
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $items[] = [
                'type' => 'encaissement',
                'num_commande' => $c['num_commande'],
                'label' => 'À encaisser — table ' . ($c['num_table'] ?? '?'),
                'href' => 'paiement.php?voir_commande=' . (int) $c['num_commande'],
            ];
        }

        return $items;
    }

    return [];
}

function dashboard_render_notifications(string $role, array $items, int $badgeCount): void
{
    $panelId = 'notifPanel';
    ?>
    <div class="notification-wrap">
        <button type="button" class="notification-btn" id="notifToggle" aria-expanded="false" aria-controls="<?php echo $panelId; ?>" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <?php if ($badgeCount > 0): ?>
            <span class="notification-badge"><?php echo (int) $badgeCount; ?></span>
            <?php endif; ?>
        </button>
        <div class="notification-panel" id="<?php echo $panelId; ?>" hidden>
            <div class="notification-panel-header">Notifications</div>
            <?php if ($items === []): ?>
                <p class="notification-panel-empty">Aucune alerte pour le moment.</p>
            <?php else: ?>
                <ul class="notification-panel-list">
                    <?php foreach ($items as $item): ?>
                    <li>
                        <?php if ($role === 'cuisinier'): ?>
                        <a href="dashboard.php#cmd-<?php echo (int) $item['num_commande']; ?>">
                            #<?php echo str_pad((string) $item['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                            — Table <?php echo htmlspecialchars((string) ($item['num_table'] ?? '—')); ?>
                            <span class="text-muted small d-block">Nouvelle commande</span>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['href']); ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/** Chaîne de recherche pour paiements récents (facture, client, table, mode). */
function dashboard_paiement_search_blob(array $row): string
{
    $parts = [
        $row['num_facture'] ?? '',
        $row['num_commande'] ?? '',
        $row['num_table'] ?? '',
        $row['nom_client'] ?? '',
        $row['prenom_client'] ?? '',
        $row['telephone_client'] ?? '',
        $row['mode_paiement'] ?? '',
        dashboard_mode_paiement_label((string) ($row['mode_paiement'] ?? '')),
        $row['total_paye'] ?? '',
        $row['montant_total'] ?? '',
    ];
    if (!empty($row['num_facture'])) {
        $parts[] = 'facture';
        $parts[] = 'F-' . str_pad((string) $row['num_facture'], 4, '0', STR_PAD_LEFT);
    }

    return mb_strtolower(implode(' ', array_filter(array_map('strval', $parts))));
}

function dashboard_mode_paiement_label(string $mode): string
{
    $labels = [
        'especes' => 'Cash / Espèces',
        'mobile' => 'Mobile money',
        'carte' => 'Carte bancaire',
    ];

    return $labels[$mode] ?? ucfirst($mode);
}
