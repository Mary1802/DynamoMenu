<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\ClientSessionService;
use App\Auth\StaffAuthService;
use App\Repository\AdminStatsRepository;
use App\Repository\BoissonRepository;
use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use App\Repository\ContactRepository;
use App\Repository\EmployeRepository;
use App\Repository\FactureRepository;
use App\Repository\PlatRepository;
use App\Repository\TableRepository;
use App\Security\Csrf;
use App\Security\OrderAccess;
use App\Security\PasswordHasher;
use App\Security\SessionCookie;
use App\Service\ActivityLogService;
use App\Service\CartService;
use App\Service\ClientPaymentService;
use App\Service\CommandeService;
use App\Service\EmployePasswordService;
use App\Service\MenuImageUploadService;
use App\Service\MenuService;
use App\Service\NotificationService;
use App\Service\OrderCreationService;
use App\Service\PaiementService;
use App\Service\ReportService;
use App\Service\QrService;
use App\Service\SchemaUpgradeService;
use App\Service\StaffNotificationService;
use App\Service\StaffSettingsService;
use App\Support\MoneyFormatter;
use App\Service\TableContextService;
use PDO;

final class Application
{
    private static ?self $instance = null;

    private readonly Config $config;
    private ?Database $database = null;
    private ?StaffAuthService $staffAuth = null;
    private ?ClientSessionService $clientSession = null;
    private ?Csrf $csrf = null;
    private ?PasswordHasher $passwordHasher = null;
    private ?OrderAccess $orderAccess = null;
    private ?EmployeRepository $employeRepository = null;
    private ?EmployePasswordService $employePasswordService = null;
    private ?ActivityLogService $activityLog = null;
    private ?CommandeRepository $commandeRepository = null;
    private ?CommandeService $commandeService = null;
    private ?FactureRepository $factureRepository = null;
    private ?PaiementService $paiementService = null;
    private ?NotificationService $notificationService = null;
    private ?CartService $cartService = null;
    private ?TableContextService $tableContextService = null;
    private ?ContactRepository $contactRepository = null;
    private ?ClientRepository $clientRepository = null;
    private ?MenuService $menuService = null;
    private ?OrderCreationService $orderCreationService = null;
    private ?ClientPaymentService $clientPaymentService = null;
    private ?AdminStatsRepository $adminStatsRepository = null;
    private ?PlatRepository $platRepository = null;
    private ?BoissonRepository $boissonRepository = null;
    private ?TableRepository $tableRepository = null;
    private ?MenuImageUploadService $menuImageUploadService = null;
    private ?ReportService $reportService = null;
    private ?StaffSettingsService $staffSettingsService = null;
    private ?SchemaUpgradeService $schemaUpgrade = null;
    private ?MoneyFormatter $moneyFormatter = null;
    private ?StaffNotificationService $staffNotificationService = null;
    private ?QrService $qrService = null;

    private function __construct()
    {
        $this->config = new Config(dirname(__DIR__, 3) . '/config');
    }

    public static function boot(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getInstance(): self
    {
        return self::boot();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function db(): PDO
    {
        if ($this->database === null) {
            $this->database = new Database($this->config);
        }

        return $this->database->pdo();
    }

    public function staffAuth(): StaffAuthService
    {
        if ($this->staffAuth === null) {
            $this->staffAuth = new StaffAuthService(
                $this->config,
                new SessionCookie($this->config),
                $this->csrf(),
                $this->db()
            );
        }

        return $this->staffAuth;
    }

    public function clientSession(): ClientSessionService
    {
        if ($this->clientSession === null) {
            $this->clientSession = new ClientSessionService(
                $this->config,
                new SessionCookie($this->config),
                $this->csrf()
            );
        }

        return $this->clientSession;
    }

    public function csrf(): Csrf
    {
        if ($this->csrf === null) {
            $this->csrf = new Csrf();
        }

        return $this->csrf;
    }

    public function passwordHasher(): PasswordHasher
    {
        if ($this->passwordHasher === null) {
            $this->passwordHasher = new PasswordHasher();
        }

        return $this->passwordHasher;
    }

    public function orderAccess(): OrderAccess
    {
        if ($this->orderAccess === null) {
            $this->orderAccess = new OrderAccess($this->config);
        }

        return $this->orderAccess;
    }

    public function employeRepository(): EmployeRepository
    {
        if ($this->employeRepository === null) {
            $this->employeRepository = new EmployeRepository($this->db());
        }

        return $this->employeRepository;
    }

    public function employePasswordService(): EmployePasswordService
    {
        if ($this->employePasswordService === null) {
            $this->employePasswordService = new EmployePasswordService(
                $this->db(),
                $this->passwordHasher()
            );
        }

        return $this->employePasswordService;
    }

    public function activityLog(): ActivityLogService
    {
        if ($this->activityLog === null) {
            $this->activityLog = new ActivityLogService($this->db());
        }

        return $this->activityLog;
    }

    public function commandeRepository(): CommandeRepository
    {
        if ($this->commandeRepository === null) {
            $this->commandeRepository = new CommandeRepository($this->db());
        }

        return $this->commandeRepository;
    }

    public function commandeService(): CommandeService
    {
        if ($this->commandeService === null) {
            $this->commandeService = new CommandeService(
                $this->commandeRepository(),
                $this->activityLog()
            );
        }

        return $this->commandeService;
    }

    public function factureRepository(): FactureRepository
    {
        if ($this->factureRepository === null) {
            $this->factureRepository = new FactureRepository($this->db());
        }

        return $this->factureRepository;
    }

    public function paiementService(): PaiementService
    {
        if ($this->paiementService === null) {
            $this->paiementService = new PaiementService(
                $this->db(),
                $this->factureRepository(),
                $this->commandeRepository()
            );
        }

        return $this->paiementService;
    }

    public function notificationService(): NotificationService
    {
        if ($this->notificationService === null) {
            $this->notificationService = new NotificationService($this->db());
        }

        return $this->notificationService;
    }

    public function cartService(): CartService
    {
        if ($this->cartService === null) {
            $this->cartService = new CartService($this->clientSession(), $this->csrf());
        }

        return $this->cartService;
    }

    public function tableContextService(): TableContextService
    {
        if ($this->tableContextService === null) {
            $this->tableContextService = TableContextService::fromApp($this);
        }

        return $this->tableContextService;
    }

    public function contactRepository(): ContactRepository
    {
        if ($this->contactRepository === null) {
            $this->contactRepository = new ContactRepository($this->db());
        }

        return $this->contactRepository;
    }

    public function clientRepository(): ClientRepository
    {
        if ($this->clientRepository === null) {
            $this->clientRepository = new ClientRepository($this->db());
        }

        return $this->clientRepository;
    }

    public function menuService(): MenuService
    {
        if ($this->menuService === null) {
            $this->menuService = new MenuService($this->db(), dirname(__DIR__, 3));
        }

        return $this->menuService;
    }

    public function orderCreationService(): OrderCreationService
    {
        if ($this->orderCreationService === null) {
            $this->orderCreationService = OrderCreationService::fromApp($this);
        }

        return $this->orderCreationService;
    }

    public function clientPaymentService(): ClientPaymentService
    {
        if ($this->clientPaymentService === null) {
            $this->clientPaymentService = ClientPaymentService::fromApp($this);
        }

        return $this->clientPaymentService;
    }

    public function adminStatsRepository(): AdminStatsRepository
    {
        if ($this->adminStatsRepository === null) {
            $this->adminStatsRepository = new AdminStatsRepository($this->db());
        }

        return $this->adminStatsRepository;
    }

    public function platRepository(): PlatRepository
    {
        if ($this->platRepository === null) {
            $this->platRepository = new PlatRepository($this->db());
        }

        return $this->platRepository;
    }

    public function boissonRepository(): BoissonRepository
    {
        if ($this->boissonRepository === null) {
            $this->boissonRepository = new BoissonRepository($this->db());
        }

        return $this->boissonRepository;
    }

    public function tableRepository(): TableRepository
    {
        if ($this->tableRepository === null) {
            $this->tableRepository = new TableRepository($this->db());
        }

        return $this->tableRepository;
    }

    public function menuImageUploadService(): MenuImageUploadService
    {
        if ($this->menuImageUploadService === null) {
            $this->menuImageUploadService = new MenuImageUploadService(dirname(__DIR__, 3));
        }

        return $this->menuImageUploadService;
    }

    public function reportService(): ReportService
    {
        if ($this->reportService === null) {
            $this->reportService = ReportService::fromApp($this);
        }

        return $this->reportService;
    }

    public function staffSettingsService(): StaffSettingsService
    {
        if ($this->staffSettingsService === null) {
            $this->staffSettingsService = StaffSettingsService::fromApp($this);
        }

        return $this->staffSettingsService;
    }

    public function schemaUpgrade(): SchemaUpgradeService
    {
        if ($this->schemaUpgrade === null) {
            $this->schemaUpgrade = SchemaUpgradeService::fromApp($this);
        }

        return $this->schemaUpgrade;
    }

    public function moneyFormatter(): MoneyFormatter
    {
        if ($this->moneyFormatter === null) {
            $this->moneyFormatter = MoneyFormatter::fromApp($this);
        }

        return $this->moneyFormatter;
    }

    public function staffNotificationService(): StaffNotificationService
    {
        if ($this->staffNotificationService === null) {
            $this->staffNotificationService = StaffNotificationService::fromApp($this);
        }

        return $this->staffNotificationService;
    }

    public function qrService(): QrService
    {
        if ($this->qrService === null) {
            $this->qrService = QrService::fromApp($this);
        }

        return $this->qrService;
    }
}
