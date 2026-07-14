<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap/app.php';

use App\Setup\SetupRunner;

SetupRunner::fromApp()->initDatabase();

