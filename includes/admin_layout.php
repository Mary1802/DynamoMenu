<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/dashboard_helpers.php';
require_once __DIR__ . '/staff_auth.php';

use App\View\Admin\AdminLayoutView;

function admin_require_auth(): array
{
    return staff_require(['admin'], '../login.php');
}

function admin_init(): PDO
{
    admin_require_auth();

    return admin_pdo();
}

function admin_pdo(): PDO
{
    return app()->db();
}

/** @param array<string, array{url:string,icon:string,label:string}> $items */
function admin_sidebar(string $active, array $items = []): void
{
    AdminLayoutView::sidebar($active, $items);
}

function admin_shell_start(string $title, string $active, string $eyebrow, string $heading, string $subtitle = ''): void
{
    AdminLayoutView::shellStart($title, $active, $eyebrow, $heading, $subtitle);
}

function admin_shell_end(): void
{
    AdminLayoutView::shellEnd();
}

function admin_log(PDO $pdo, string $action, string $description, string $module = 'admin'): void
{
    app()->activityLog()->log($action, $description, $module);
}
