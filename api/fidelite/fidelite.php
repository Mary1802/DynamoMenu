<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
require_once dirname(__DIR__, 2) . '/includes/client_session.php';

use App\Controller\Api\Fidelite\FideliteController;

(new FideliteController())->handle($_GET, $_POST);
