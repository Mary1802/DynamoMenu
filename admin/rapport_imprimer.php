<?php

require_once __DIR__ . '/../includes/admin_layout.php';

use App\Controller\Admin\ReportController;

admin_require_auth();
(new ReportController())->export($_GET, true);
