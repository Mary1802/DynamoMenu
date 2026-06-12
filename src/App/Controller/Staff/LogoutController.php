<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Core\Application;

final class LogoutController
{
    public function handle(string $redirect = 'login.php?logout=1'): never
    {
        Application::getInstance()->staffAuth()->logout();
        header('Location: ' . $redirect);
        exit;
    }
}
