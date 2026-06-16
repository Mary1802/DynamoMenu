<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Application;
use App\View\Admin\AdminLayoutView;
use PDO;

final class AdminPage
{
    /** @return array{user_id:int,nom:string,email:string,role:string,login_at:int} */
    public static function requireAuth(): array
    {
        return StaffPage::require(['admin'], '../login.php');
    }

    public static function init(): PDO
    {
        self::requireAuth();

        return Application::getInstance()->db();
    }

    public static function log(string $action, string $description, string $module = 'admin'): void
    {
        Application::getInstance()->activityLog()->log($action, $description, $module);
    }

    /** @param array<string, array{url:string,icon:string,label:string}> $items */
    public static function sidebar(string $active, array $items = []): void
    {
        AdminLayoutView::sidebar($active, $items);
    }

    public static function shellStart(string $title, string $active, string $eyebrow, string $heading, string $subtitle = ''): void
    {
        AdminLayoutView::shellStart($title, $active, $eyebrow, $heading, $subtitle);
    }

    public static function shellEnd(): void
    {
        AdminLayoutView::shellEnd();
    }
}
