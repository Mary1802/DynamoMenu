<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Controller\Api\Paiement\PaiementController;

(new PaiementController())->handle($_GET);
