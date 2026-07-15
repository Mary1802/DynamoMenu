<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Application;
use App\Model\CommandeLine;
use App\Support\PaymentLabels;
use App\View\Admin\MenuFormView;
use App\View\Staff\DashboardLayoutView;
use App\View\Staff\OrderDetailView;
use PDO;

/** Helpers dashboards employés (remplace includes/dashboard_helpers.php). */
final class Dashboard
{
    public static function assetLinks(string $pageTitle): void
    {
        DashboardLayoutView::assetLinks($pageTitle);
    }

    public static function scripts(): void
    {
        DashboardLayoutView::scripts();
    }

    public static function themeToggle(): void
    {
        DashboardLayoutView::themeToggle();
    }

    public static function csrfField(): void
    {
        Application::getInstance()->csrf()->field();
    }

    public static function sidebarUserFooter(string $context): void
    {
        DashboardLayoutView::sidebarUserFooter($context);
    }

    public static function orderItemsList(?string $detailsPlats): array
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
    public static function contacts(?PDO $pdo = null): array
    {
        return Application::getInstance()->staffSettingsService()->primaryContact();
    }

    /** @return list<array<string, mixed>> */
    public static function contactList(PDO $pdo): array
    {
        return Application::getInstance()->staffSettingsService()->contactList();
    }

    /** @return list<array<string, mixed>> */
    public static function fetchOrderLines(PDO $pdo, int $numCommande): array
    {
        return array_map(
            static fn(CommandeLine $line): array => $line->toArray(),
            Application::getInstance()->commandeRepository()->fetchLines($numCommande)
        );
    }

    /** @param array<string, mixed> $line */
    public static function lineLabel(array $line): string
    {
        return Application::getInstance()->commandeService()->lineLabel($line);
    }

    /** @param array<string, mixed> $row */
    public static function orderSearchBlob(array $row, bool $includeClient = true): string
    {
        $parts = [
            $row['num_commande'] ?? '',
            $row['num_table'] ?? '',
            $row['details_plats'] ?? '',
            $row['details_search'] ?? '',
            $row['instructions_speciales'] ?? '',
        ];

        if ($includeClient) {
            $parts[] = $row['nom_client'] ?? '';
            $parts[] = $row['prenom_client'] ?? '';
            $parts[] = $row['telephone_client'] ?? '';
        }

        if (!empty($row['lignes']) && is_array($row['lignes'])) {
            foreach ($row['lignes'] as $line) {
                $parts[] = self::lineLabel($line);
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

    /** @param list<array<string, mixed>> $orders */
    public static function attachOrderLines(PDO $pdo, array &$orders): void
    {
        Application::getInstance()->commandeService()->attachLines($orders);
    }

    /** @param list<array<string, mixed>> $lignes */
    public static function renderKitchenOrderDetails(array $lignes): void
    {
        OrderDetailView::kitchenLines($lignes);
    }

    public static function renderKitchenInstructions(?string $instructions): void
    {
        OrderDetailView::kitchenInstructions($instructions);
    }

    /** @param array<string, mixed> $commande */
    /** @param array<string, string> $statutLabels */
    public static function renderCuisineCommandeFullDetail(array $commande, array $statutLabels): void
    {
        OrderDetailView::cuisineFullDetail($commande, $statutLabels);
    }

    /** @param array<string, mixed> $commande */
    /** @param array<string, string> $statutLabels */
    public static function renderCaissierCommandeDetail(array $commande, array $statutLabels): void
    {
        OrderDetailView::caissierFullDetail($commande, $statutLabels);
    }

    /** @param array{user_id:int,nom:string,email:string,role:string,login_at:int}|null $user */
    /** @return array<string, mixed> */
    public static function staffAccount(PDO $pdo, ?array $user): array
    {
        return Application::getInstance()->staffSettingsService()->staffAccount($user);
    }

    /** @return array{nb:int,ca:float,ca_especes:float,ca_mobile:float,ca_carte:float} */
    public static function salesTotals(PDO $pdo, string $scope, string $value): array
    {
        return Application::getInstance()->adminStatsRepository()->salesTotals($scope, $value);
    }

    public static function reportMonthKey(int $annee, int $mois): string
    {
        return Application::getInstance()->reportService()->monthKey($annee, $mois);
    }

    /** @return array{annee:int,mois:int,mois_key:string} */
    public static function reportParsePeriod(?int $annee, ?int $mois): array
    {
        return Application::getInstance()->reportService()->parsePeriod($annee, $mois);
    }

    public static function reportMonthLabel(int $annee, int $mois): string
    {
        return Application::getInstance()->reportService()->monthLabel($annee, $mois);
    }

    /** @return list<string> */
    public static function platCategories(PDO $pdo): array
    {
        return Application::getInstance()->platRepository()->listCategories();
    }

    /** @param list<string> $categories */
    public static function renderPlatCategorieSelect(
        string $name,
        string $selected,
        array $categories,
        bool $small = false,
        bool $required = false,
        ?string $formId = null
    ): void {
        MenuFormView::platCategorySelect($name, $selected, $categories, $small, $required, $formId);
    }

    /** @return list<string> */
    public static function boissonTypes(PDO $pdo): array
    {
        return Application::getInstance()->boissonRepository()->listTypeNames();
    }

    /** @param list<string> $types */
    public static function renderBoissonTypeSelect(
        string $name,
        string $selected,
        array $types,
        bool $small = false,
        bool $required = false,
        ?string $formId = null
    ): void {
        MenuFormView::boissonTypeSelect($name, $selected, $types, $small, $required, $formId);
    }

    /** @return list<array<string, mixed>> */
    public static function fetchFacturesLignes(PDO $pdo, string $monthYm, ?string $dayYmd = null): array
    {
        return Application::getInstance()->factureRepository()->fetchReportLines($monthYm, $dayYmd);
    }

    /** @return list<array<string, mixed>> */
    public static function staffNotifications(PDO $pdo, string $role): array
    {
        return Application::getInstance()->staffNotificationService()->forRole($role);
    }

    /** @param list<array<string, mixed>> $items */
    public static function renderNotifications(string $role, array $items, int $badgeCount): void
    {
        DashboardLayoutView::notifications($role, $items, $badgeCount);
    }

    /** @param array<string, mixed> $row */
    public static function paiementSearchBlob(array $row): string
    {
        $parts = [
            $row['num_facture'] ?? '',
            $row['num_commande'] ?? '',
            $row['num_table'] ?? '',
            $row['nom_client'] ?? '',
            $row['prenom_client'] ?? '',
            $row['telephone_client'] ?? '',
            $row['mode_paiement'] ?? '',
            self::modePaiementLabel((string) ($row['mode_paiement'] ?? '')),
            $row['total_paye'] ?? '',
            $row['montant_total'] ?? '',
        ];
        if (!empty($row['num_facture'])) {
            $parts[] = 'facture';
            $parts[] = 'F-' . str_pad((string) $row['num_facture'], 4, '0', STR_PAD_LEFT);
        }

        return mb_strtolower(implode(' ', array_filter(array_map('strval', $parts))));
    }

    public static function modePaiementLabel(string $mode): string
    {
        return PaymentLabels::dashboardMode($mode);
    }
}
