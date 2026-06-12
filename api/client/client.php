<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Controller\Api\Client\ClientProfileController;

(new ClientProfileController())->handle($_GET);
