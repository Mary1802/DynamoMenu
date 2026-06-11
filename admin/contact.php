<?php

require_once __DIR__ . '/../includes/admin_layout.php';
admin_require_auth();

/**
 * Redirection — la gestion des contacts est dans Paramètres.
 */
$target = 'parametres.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $target . '#contacts-admin', true, 302);
exit;
