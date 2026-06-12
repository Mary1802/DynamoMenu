<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/client_session.php';

use App\Controller\Client\PaymentRequestController;

(new PaymentRequestController())->handle($_POST);
