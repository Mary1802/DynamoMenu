<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Controller\Api\Client\CartCountController;

(new CartCountController())->handle();
