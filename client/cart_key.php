<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/client_session.php';

use App\Controller\Api\Client\CartKeyController;

app()->clientSession()->start();
(new CartKeyController())->handle($_GET, $_POST);
