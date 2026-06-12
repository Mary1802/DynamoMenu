<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
require_once dirname(__DIR__, 2) . '/includes/client_session.php';

use App\Controller\Api\Client\NotificationsController;

(new NotificationsController())->handle($_GET);
