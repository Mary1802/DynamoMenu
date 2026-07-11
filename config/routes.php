<?php

declare(strict_types=1);

use App\Controller\Admin\ClientController;
use App\Controller\Admin\CommandeController as AdminCommandeController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\EmployeController;
use App\Controller\Admin\LogController;
use App\Controller\Admin\NotificationController;
use App\Controller\Admin\ParametresController;
use App\Controller\Admin\PlatController;
use App\Controller\Admin\ReportController as AdminReportController;
use App\Controller\Admin\TableController;
use App\Controller\Api\Client\CartCountController;
use App\Controller\Api\Client\CartKeyController;
use App\Controller\Api\Client\CommandeStatutController;
use App\Controller\Api\Client\NotificationsController;
use App\Controller\Api\Commande\CommandeController as ApiCommandeController;
use App\Controller\Api\Employe\EmployeController as ApiEmployeController;
use App\Controller\Api\Menu\MenuController as ApiMenuController;
use App\Controller\Api\Paiement\PaiementController as ApiPaiementController;
use App\Controller\Api\Stats\StatsController;
use App\Controller\Caissier\CommandeListController as CaissierCommandeListController;
use App\Controller\Caissier\FactureController;
use App\Controller\Caissier\PaiementController as CaissierPaiementController;
use App\Controller\Caissier\ReportController as CaissierReportController;
use App\Controller\Client\CartController;
use App\Controller\Client\ClearSessionController;
use App\Controller\Client\CommandeController as ClientCommandeController;
use App\Controller\Client\ClientIdentificationController;
use App\Controller\Client\ConfirmationController;
use App\Controller\Client\HomeController;
use App\Controller\Client\MenuController;
use App\Controller\Client\NouvelleCommandeController;
use App\Controller\Client\OrderSuccessController;
use App\Controller\Client\OrderHistoryController;
use App\Controller\Client\OrderTrackingController;
use App\Controller\Client\PaymentConfirmationController;
use App\Controller\Client\PaymentController;
use App\Controller\Client\PaymentRequestController;
use App\Controller\Cuisine\CommandeListController as CuisineCommandeListController;
use App\Controller\Cuisine\KitchenDashboardController;
use App\Controller\Manager\CommandeListController as ManagerCommandeListController;
use App\Controller\Manager\ManagerDashboardController;
use App\Controller\Staff\LoginController;
use App\Controller\Staff\LogoutController;
use App\Controller\Staff\ParametresController as StaffParametresController;

return [
    // ── Admin ──────────────────────────────────────────────────────────
    'admin/dashboard.php' => [
        'auth' => 'staff:admin',
        'controller' => [DashboardController::class, 'index'],
        'args' => [],
    ],
    'admin/clients.php' => [
        'auth' => 'admin',
        'controller' => [ClientController::class, 'handle'],
        'args' => ['get', 'post'],
    ],
    'admin/commandes.php' => [
        'auth' => 'admin',
        'setup' => ['schema'],
        'controller' => [AdminCommandeController::class, 'handle'],
        'args' => ['get', 'post'],
    ],
    'admin/contact.php' => [
        'auth' => 'admin.auth',
        'redirect' => 'parametres.php#contacts-admin',
        'preserve_query' => true,
    ],
    'admin/employes.php' => [
        'auth' => 'admin',
        'setup' => ['schema'],
        'controller' => [EmployeController::class, 'handle'],
        'args' => ['get', 'post', 'session'],
    ],
    'admin/logs.php' => [
        'auth' => 'admin',
        'controller' => [LogController::class, 'handle'],
        'args' => ['get'],
    ],
    'admin/notifications.php' => [
        'auth' => 'admin',
        'controller' => [NotificationController::class, 'handle'],
        'args' => ['get', 'post'],
    ],
    'admin/parametres.php' => [
        'auth' => 'admin',
        'controller' => [ParametresController::class, 'handle'],
        'args' => ['get', 'post', 'staff_user'],
    ],
    'admin/plats.php' => [
        'auth' => 'admin',
        'controller' => [PlatController::class, 'handle'],
        'args' => ['get', 'post', 'files'],
    ],
    'admin/rapport_export.php' => [
        'auth' => 'admin.auth',
        'controller' => [AdminReportController::class, 'export'],
        'args' => ['get', 'export_download'],
        'response' => 'action',
    ],
    'admin/rapport_imprimer.php' => [
        'auth' => 'admin.auth',
        'controller' => [AdminReportController::class, 'export'],
        'args' => ['get', 'export_inline'],
        'response' => 'action',
    ],
    'admin/rapports.php' => [
        'auth' => 'admin',
        'controller' => [AdminReportController::class, 'index'],
        'args' => ['get'],
    ],
    'admin/tables.php' => [
        'auth' => 'admin',
        'controller' => [TableController::class, 'handle'],
        'args' => ['post'],
    ],

    // ── Client ─────────────────────────────────────────────────────────
    'client/index.php' => [
        'controller' => [HomeController::class, 'index'],
        'args' => [],
    ],
    'client/identite.php' => [
        'auth' => 'client.session',
        'controller' => [ClientIdentificationController::class, 'handle'],
        'args' => ['post'],
    ],
    'client/menu.php' => [
        'controller' => [MenuController::class, 'index'],
        'args' => [],
    ],
    'client/panier.php' => [
        'controller' => [CartController::class, 'handle'],
        'args' => ['get', 'post'],
    ],
    'client/nouvelle_commande.php' => [
        'controller' => [NouvelleCommandeController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],
    'client/cart_key.php' => [
        'auth' => 'client.session',
        'controller' => [CartKeyController::class, 'handle'],
        'args' => ['get', 'post'],
        'response' => 'action',
    ],
    'client/clear_session.php' => [
        'controller' => [ClearSessionController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],
    'client/get_cart_count.php' => [
        'controller' => [CartCountController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],
    'client/commande.php' => [
        'controller' => [ClientCommandeController::class, 'redirect'],
        'args' => [],
        'response' => 'action',
    ],
    'client/confirmation.php' => [
        'controller' => [ConfirmationController::class, 'handle'],
        'args' => ['post'],
    ],
    'client/confirmation_paiement.php' => [
        'controller' => [PaymentConfirmationController::class, 'show'],
        'args' => ['get'],
    ],
    'client/confirmation_success.php' => [
        'controller' => [OrderSuccessController::class, 'show'],
        'args' => ['get'],
    ],
    'client/paiement_client.php' => [
        'controller' => [PaymentController::class, 'show'],
        'args' => ['get'],
    ],
    'client/suivi_commande.php' => [
        'controller' => [OrderTrackingController::class, 'show'],
        'args' => ['get'],
    ],
    'client/mes_commandes.php' => [
        'controller' => [OrderHistoryController::class, 'index'],
    ],
    'client/traitement_paiement.php' => [
        'controller' => [PaymentRequestController::class, 'handle'],
        'args' => ['post'],
        'response' => 'action',
    ],

    // ── Cuisine ────────────────────────────────────────────────────────
    'cuisine/dashboard.php' => [
        'auth' => 'staff:cuisinier',
        'invoke' => static function (): array {
            $kitchen = new KitchenDashboardController();
            $kitchen->handlePost($_POST);

            return $kitchen->index();
        },
    ],
    'cuisine/commandes.php' => [
        'auth' => 'staff:cuisinier',
        'controller' => [CuisineCommandeListController::class, 'index'],
        'args' => ['get'],
    ],
    'cuisine/parametres.php' => [
        'auth' => 'staff:cuisinier',
        'controller' => [StaffParametresController::class, 'index'],
        'args' => ['staff_user'],
    ],

    // ── Manager ──────────────────────────────────────────────────────
    'manager/dashboard.php' => [
        'auth' => 'staff:manager',
        'invoke' => static function (): array {
            $manager = new ManagerDashboardController();
            $manager->handlePost($_POST);

            return $manager->index();
        },
    ],
    'manager/commandes.php' => [
        'auth' => 'staff:manager',
        'invoke' => static function (): array {
            $list = new ManagerCommandeListController();
            $list->handlePost($_POST);

            return $list->index($_GET);
        },
    ],
    'manager/parametres.php' => [
        'auth' => 'staff:manager',
        'controller' => [StaffParametresController::class, 'index'],
        'args' => ['staff_user'],
    ],

    // ── Caissier ───────────────────────────────────────────────────────
    'caissier/commandes.php' => [
        'auth' => 'staff:caissier',
        'controller' => [CaissierCommandeListController::class, 'index'],
        'args' => [],
    ],
    'caissier/generer_facture.php' => [
        'auth' => 'staff:caissier',
        'controller' => [FactureController::class, 'show'],
        'args' => ['get'],
    ],
    'caissier/paiement.php' => [
        'auth' => 'staff:caissier',
        'controller' => [CaissierPaiementController::class, 'handle'],
        'args' => ['get', 'post'],
    ],
    'caissier/parametres.php' => [
        'auth' => 'staff:caissier',
        'controller' => [StaffParametresController::class, 'index'],
        'args' => ['staff_user'],
    ],
    'caissier/rapport_export.php' => [
        'auth' => 'staff:caissier',
        'controller' => [CaissierReportController::class, 'export'],
        'args' => ['get', 'export_download'],
        'response' => 'action',
    ],
    'caissier/rapport_imprimer.php' => [
        'auth' => 'staff:caissier',
        'controller' => [CaissierReportController::class, 'export'],
        'args' => ['get', 'export_inline'],
        'response' => 'action',
    ],
    'caissier/rapports.php' => [
        'auth' => 'staff:caissier',
        'controller' => [CaissierReportController::class, 'index'],
        'args' => ['get'],
    ],

    // ── API ────────────────────────────────────────────────────────────
    'api/client/commande_statut.php' => [
        'controller' => [CommandeStatutController::class, 'handle'],
        'args' => ['get'],
        'response' => 'action',
    ],
    'api/client/notifications.php' => [
        'controller' => [NotificationsController::class, 'handle'],
        'args' => ['get'],
        'response' => 'action',
    ],
    'api/employe/employe.php' => [
        'controller' => [ApiEmployeController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],
    'api/stats/stats.php' => [
        'controller' => [StatsController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],
    'api/paiement/paiement.php' => [
        'controller' => [ApiPaiementController::class, 'handle'],
        'args' => ['get'],
        'response' => 'action',
    ],
    'api/commande/commande.php' => [
        'controller' => [ApiCommandeController::class, 'handle'],
        'args' => ['get'],
        'response' => 'action',
    ],
    'api/menu/menu.php' => [
        'controller' => [ApiMenuController::class, 'handle'],
        'args' => [],
        'response' => 'action',
    ],

    // ── Racine ─────────────────────────────────────────────────────────
    'login.php' => [
        'setup' => ['schema'],
        'controller' => [LoginController::class, 'handle'],
        'args' => [],
    ],
    'logout.php' => [
        'controller' => [LogoutController::class, 'handle'],
        'args' => ['logout_redirect'],
        'response' => 'action',
    ],
];
