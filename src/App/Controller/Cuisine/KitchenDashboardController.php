<?php

declare(strict_types=1);

namespace App\Controller\Cuisine;

use App\Core\Application;
use App\Service\CommandeService;

final class KitchenDashboardController
{
    private Application $app;
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->app = $app;
        $this->commandes = $app->commandeService();
    }

    public function handlePost(array $post): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string) ($post['action'] ?? '');
        $commandeId = (int) ($post['commande_id'] ?? 0);

        if ($commandeId <= 0 || $action === '') {
            return;
        }

        $this->commandes->handleKitchenAction($action, $commandeId);
        header('Location: dashboard.php');
        exit;
    }

    /** @param array<string, mixed> $post */
    public function handle(array $post): array
    {
        $this->handlePost($post);

        return $this->index();
    }

    /**
     * @return array{
     *   stats: array{en_attente:int,en_preparation:int,prete:int},
     *   commandes_actives: list<array<string,mixed>>,
     *   commandes_terminees: list<array<string,mixed>>,
     *   dashboard_error: ?string,
     *   notif_count: int,
     *   notif_items: list<array<string,mixed>>
     * }
     */
    public function index(): array
    {
        $stats = $this->commandes->kitchenStats();
        $commandesActives = [];
        $commandesTerminees = [];
        $dashboardError = null;

        try {
            $repo = $this->commandes->repository();
            $commandesActives = $repo->findKitchenActive();
            $commandesTerminees = $repo->findKitchenReady(10);
            $this->commandes->attachLines($commandesActives);
            $this->commandes->attachLines($commandesTerminees);
        } catch (\PDOException $e) {
            $dashboardError = 'Impossible de charger les commandes. Vérifiez que la base est à jour via init_db.php ou run_update.php.';
        }

        return [
            'stats' => $stats,
            'commandes_actives' => $commandesActives,
            'commandes_terminees' => $commandesTerminees,
            'dashboard_error' => $dashboardError,
            'notif_count' => (int) $stats['en_attente'],
            'notif_items' => $this->app->staffNotificationService()->forRole('cuisinier'),
        ];
    }
}
