<?php

declare(strict_types=1);

namespace App\View\Staff;

use App\Core\Application;
use App\View\View;

final class DashboardLayoutView
{
    public static function assetLinks(string $pageTitle): void
    {
        View::render('staff/asset-links', ['pageTitle' => $pageTitle]);
        Application::getInstance()->staffAuth()->startSession();
        Application::getInstance()->csrf()->metaTag();
    }

    public static function scripts(): void
    {
        View::render('staff/scripts');
    }

    public static function themeToggle(): void
    {
        View::render('staff/theme-toggle');
    }

    public static function sidebarUserFooter(string $context): void
    {
        $auth = Application::getInstance()->staffAuth();
        $user = $auth->user();
        View::render('staff/sidebar-user-footer', [
            'nom' => (string) ($user['nom'] ?? $_SESSION['nom'] ?? 'Utilisateur'),
            'roleLabel' => $auth->roleLabel((string) ($user['role'] ?? $context)),
        ]);
    }

    /** @param list<array<string, mixed>> $items */
    public static function notifications(string $role, array $items, int $badgeCount): void
    {
        View::render('staff/notifications', [
            'role' => $role,
            'items' => $items,
            'badgeCount' => $badgeCount,
        ]);
    }
}
