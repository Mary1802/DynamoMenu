<?php

require_once __DIR__ . '/bootstrap/app.php';

use App\Controller\Staff\LogoutController;

(new LogoutController())->handle('login.php?logout=1');
