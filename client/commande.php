<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Controller\Client\CommandeController;

(new CommandeController())->redirect();
