<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Controller\Api\Menu\MenuController;

(new MenuController())->handle();
