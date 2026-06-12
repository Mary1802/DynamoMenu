<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/client_session.php';

use App\Controller\Client\ClearSessionController;

(new ClearSessionController())->handle();
