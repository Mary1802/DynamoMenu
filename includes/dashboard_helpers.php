<?php
/**
 * Helpers partagés pour les dashboards employés (ponts données + vues).
 */

use App\Core\Application;
use App\Support\PaymentLabels;
use App\View\Admin\MenuFormView;
use App\View\Staff\DashboardLayoutView;
use App\View\Staff\OrderDetailView;

function dashboard_asset_links(string $pageTitle): void
{
    DashboardLayoutView::assetLinks($pageTitle);
}

function dashboard_scripts(): void
{
    DashboardLayoutView::scripts();
}

function dashboard_sidebar_user_footer(string $context): void
{
    DashboardLayoutView::sidebarUserFooter($context);
}

/**
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_demandes_paiement(PDO $pdo): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->factureRepository()->findPendingDemandes();
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
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->staffSettingsService()->primaryContact();
}

/**
 * @return list<array<string, mixed>>
 */
function dashboard_contact_list(PDO $pdo): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->staffSettingsService()->contactList();
}

/**
 * Lignes détaillées d'une commande (plats / boissons).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_order_lines(PDO $pdo, int $numCommande): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return array_map(
        static fn(\App\Model\CommandeLine $line): array => $line->toArray(),
        app()->commandeRepository()->fetchLines($numCommande)
    );
}

function dashboard_line_label(array $line): string
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->commandeService()->lineLabel($line);
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
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    app()->commandeService()->attachLines($orders);
}

/**
 * @param list<array<string, mixed>> $lignes
 */
function dashboard_render_kitchen_order_details(array $lignes): void
{
    OrderDetailView::kitchenLines($lignes);
}

function dashboard_render_kitchen_instructions(?string $instructions): void
{
    OrderDetailView::kitchenInstructions($instructions);
}

function dashboard_render_cuisine_commande_full_detail(array $commande, array $statutLabels): void
{
    OrderDetailView::cuisineFullDetail($commande, $statutLabels);
}

function dashboard_render_caissier_commande_detail(array $commande, array $statutLabels): void
{
    OrderDetailView::caissierFullDetail($commande, $statutLabels);
}

/** Infos employé connecté (sans mot de passe). */
function dashboard_staff_account(PDO $pdo, ?array $user): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->staffSettingsService()->staffAccount($user);
}

/**
 * @return array{nb:int,ca:float,ca_especes:float,ca_mobile:float,ca_carte:float}
 */
function dashboard_sales_totals(PDO $pdo, string $scope, string $value): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->adminStatsRepository()->salesTotals($scope, $value);
}

function dashboard_report_month_key(int $annee, int $mois): string
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->reportService()->monthKey($annee, $mois);
}

/** @return array{annee:int,mois:int,mois_key:string} */
function dashboard_report_parse_period(?int $annee, ?int $mois): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->reportService()->parsePeriod($annee, $mois);
}

function dashboard_report_month_label(int $annee, int $mois): string
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->reportService()->monthLabel($annee, $mois);
}

/**
 * Catégories de plats pour l’admin (menu carte + valeurs déjà en base).
 *
 * @return list<string>
 */
function dashboard_plat_categories(PDO $pdo): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->platRepository()->listCategories();
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
    MenuFormView::platCategorySelect($name, $selected, $categories, $small, $required);
}

/**
 * Types de boisson présents en base (table type_boisson).
 *
 * @return list<string>
 */
function dashboard_boisson_types(PDO $pdo): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->boissonRepository()->listTypeNames();
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
    MenuFormView::boissonTypeSelect($name, $selected, $types, $small, $required);
}

/**
 * Lignes factures pour rapport caisse (mois entier ou un jour).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_factures_lignes(PDO $pdo, string $monthYm, ?string $dayYmd = null): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->factureRepository()->fetchReportLines($monthYm, $dayYmd);
}

/**
 * Notifications cuisine / caisse (commandes actives).
 *
 * @return list<array<string, mixed>>
 */
function dashboard_staff_notifications(PDO $pdo, string $role): array
{
    if (!function_exists('app')) {
        require_once __DIR__ . '/bootstrap.php';
    }

    return app()->staffNotificationService()->forRole($role);
}

function dashboard_render_notifications(string $role, array $items, int $badgeCount): void
{
    DashboardLayoutView::notifications($role, $items, $badgeCount);
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
    return PaymentLabels::dashboardMode($mode);
}
