<?php

declare(strict_types=1);

namespace App\Controller\Client;

final class CommandeController
{
    public function redirect(): never
    {
        header('Location: menu.php');
        exit;
    }
}
