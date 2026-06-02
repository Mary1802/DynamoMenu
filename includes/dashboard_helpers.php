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
    <link rel="stylesheet" href="../assets/css/dashboards.css?v=8">
HTML;
}

function dashboard_scripts(): void
{
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="../assets/js/dashboard.js?v=3"></script>';
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

/** @return array<string, string> */
function dashboard_contacts(): array
{
    $app = is_file(dirname(__DIR__) . '/config/app.php')
        ? require dirname(__DIR__) . '/config/app.php'
        : [];

    return is_array($app['contacts'] ?? null) ? $app['contacts'] : [];
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

/** Chaîne pour filtre recherche (nom, prénom, tél., table, n° commande). */
function dashboard_order_search_blob(array $row): string
{
    $parts = [
        $row['num_commande'] ?? '',
        $row['nom_client'] ?? '',
        $row['prenom_client'] ?? '',
        $row['telephone_client'] ?? '',
        $row['num_table'] ?? '',
        $row['details_plats'] ?? '',
    ];

    return mb_strtolower(implode(' ', array_filter(array_map('strval', $parts))));
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
            WHERE c.statut IN ('en_attente', 'en_preparation', 'prete')
            ORDER BY c.date_commande ASC
            LIMIT 12
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
                            <span class="text-muted small d-block"><?php echo htmlspecialchars($item['statut']); ?></span>
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

function dashboard_mode_paiement_label(string $mode): string
{
    $labels = [
        'especes' => 'Cash / Espèces',
        'mobile' => 'Mobile money',
        'carte' => 'Carte bancaire',
    ];

    return $labels[$mode] ?? ucfirst($mode);
}
