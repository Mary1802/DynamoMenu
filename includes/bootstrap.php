<?php

declare(strict_types=1);

use App\Core\Application;

if (!class_exists(Application::class, false)) {
    require_once dirname(__DIR__) . '/bootstrap/app.php';
}

function app(): Application
{
    return Application::getInstance();
}
