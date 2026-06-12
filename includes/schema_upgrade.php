<?php

/** Pont procédural → App\Service\SchemaUpgradeService */
require_once __DIR__ . '/bootstrap.php';

function schema_upgrade(PDO $pdo): void
{
    app()->schemaUpgrade()->run();
}
