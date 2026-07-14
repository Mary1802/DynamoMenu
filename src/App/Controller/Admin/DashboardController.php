<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Repository\AdminStatsRepository;

final class DashboardController
{
    private AdminStatsRepository $stats;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->stats = $app->adminStatsRepository();
    }

    /**
     * @return array{
     *   stats: array<string, int|float>,
     *   recent_orders: list<array<string,mixed>>,
     *   top_plats: list<array<string,mixed>>,
     *   ca_jour: array<string,mixed>,
     *   ca_mois: array<string,mixed>
     * }
     */
    public function index(): array
    {
        $jour = date('Y-m-d');
        $mois = date('Y-m');
        $caJour = $this->stats->salesTotals('day', $jour);
        $caMois = $this->stats->salesTotals('month', $mois);

        return [
            'stats' => $this->stats->dashboardStats((float) $caJour['ca'], (float) $caMois['ca']),
            'recent_orders' => $this->stats->recentOrders(3),
            'top_plats' => $this->stats->topPlats(25),
            'ca_jour' => $caJour,
            'ca_mois' => $caMois,
        ];
    }
}
