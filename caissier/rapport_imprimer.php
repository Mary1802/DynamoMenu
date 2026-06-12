<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/staff_auth.php';

use App\Controller\Caissier\ReportController;

staff_require(['caissier']);
(new ReportController())->export($_GET, true);
