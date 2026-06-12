<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
require_once dirname(__DIR__, 2) . '/includes/staff_auth.php';

use App\Controller\Api\Employe\EmployeController;

(new EmployeController())->handle();
