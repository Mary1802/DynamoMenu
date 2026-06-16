<?php

/**
 * Script pour exécuter les mises à jour de la base de données.
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Core\Application;
use App\Setup\LegacyDatabaseUpdater;
use App\Setup\SetupGuard;
use App\Setup\SetupHtmlRenderer;
use PDOException;

SetupGuard::fromApp()->requireAccess();
Application::getInstance()->staffAuth()->startSession();

try {
    $log = LegacyDatabaseUpdater::fromApp()->run();
    if (PHP_SAPI === 'cli') {
        foreach ($log as $entry) {
            echo $entry . PHP_EOL;
        }
    } else {
        SetupHtmlRenderer::updatePage($log);
    }
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    SetupHtmlRenderer::connectionError($e->getMessage());
}
