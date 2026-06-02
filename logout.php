<?php

require_once __DIR__ . '/includes/staff_auth.php';

staff_logout();

header('Location: login.php?logout=1');
exit;
