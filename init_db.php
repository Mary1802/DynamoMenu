<?php

/**
 * Script d'initialisation de la base de données DynamoMenu.
 * À exécuter une seule fois pour configurer les tables et les données de test.
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Setup\DatabaseInitializer;
use App\Setup\SetupGuard;
use App\Setup\SetupHtmlRenderer;
use PDOException;

SetupGuard::fromApp()->requireAccess();

try {
    DatabaseInitializer::fromApp()->run();
    if (PHP_SAPI === 'cli') {
        SetupHtmlRenderer::cliSuccess('Base de données initialisée.');
    } else {
        SetupHtmlRenderer::initSuccess();
    }
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    SetupHtmlRenderer::initError($e->getMessage());
}
