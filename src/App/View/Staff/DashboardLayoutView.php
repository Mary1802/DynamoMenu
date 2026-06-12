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
        require_once dirname(__DIR__, 4) . '/includes/session_security.php';
        csrf_meta_tag();
    }

    public static function scripts(): void
    {
        View::render('staff/scripts');
    }

    public static function sidebarUserFooter(string $context): void
    {
        require_once dirname(__DIR__, 4) . '/includes/staff_auth.php';
        $user = staff_user();
        View::render('staff/sidebar-user-footer', [
            'nom' => (string) ($user['nom'] ?? $_SESSION['nom'] ?? 'Utilisateur'),
            'roleLabel' => staff_role_label((string) ($user['role'] ?? $context)),
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
