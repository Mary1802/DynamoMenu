<?php

declare(strict_types=1);

namespace App\View\Admin;

use App\View\Staff\DashboardLayoutView;
use App\View\View;

final class AdminLayoutView
{
    /** @param array<string, array{url:string,icon:string,label:string}> $items */
    public static function sidebar(string $active, array $items = []): void
    {
        if ($items === []) {
            $items = [
                'dashboard' => ['url' => 'dashboard.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
                'tables' => ['url' => 'tables.php', 'icon' => 'bi-qr-code', 'label' => 'Tables & QR'],
                'commandes' => ['url' => 'commandes.php', 'icon' => 'bi-receipt', 'label' => 'Commandes'],
                'plats' => ['url' => 'plats.php', 'icon' => 'bi-grid', 'label' => 'Menu (plats)'],
                'clients' => ['url' => 'clients.php', 'icon' => 'bi-people', 'label' => 'Clients'],
                'fidelite' => ['url' => 'fidelite.php', 'icon' => 'bi-gift', 'label' => 'Fidélité'],
                'notifications' => ['url' => 'notifications.php', 'icon' => 'bi-bell', 'label' => 'Notifications'],
                'employes' => ['url' => 'employes.php', 'icon' => 'bi-person-badge', 'label' => 'Employés'],
                'rapports' => ['url' => 'rapports.php', 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Rapports ventes'],
                'parametres' => ['url' => 'parametres.php', 'icon' => 'bi-gear', 'label' => 'Paramètres'],
                'logs' => ['url' => 'logs.php', 'icon' => 'bi-journal-text', 'label' => 'Journaux'],
            ];
        }
        View::render('admin/sidebar', ['active' => $active, 'items' => $items]);
    }

    public static function shellStart(string $title, string $active, string $eyebrow, string $heading, string $subtitle = ''): void
    {
        require_once dirname(__DIR__, 4) . '/includes/staff_auth.php';
        staff_require(['admin'], '../login.php');
        View::render('admin/shell-start', [
            'title' => $title,
            'active' => $active,
            'eyebrow' => $eyebrow,
            'heading' => $heading,
            'subtitle' => $subtitle,
        ]);
    }

    public static function shellEnd(): void
    {
        View::render('admin/shell-end');
    }
}
