<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Http\Kernel;

Kernel::forFile(__FILE__);
