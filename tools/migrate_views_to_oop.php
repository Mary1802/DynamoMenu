<?php

declare(strict_types=1);

/**
 * Migration one-shot : déplace le HTML des entry scripts vers src/App/View/templates/
 * et réduit chaque entry à FrontController::run(__FILE__).
 *
 * Usage: php tools/migrate_views_to_oop.php
 */

$root = dirname(__DIR__);

$pages = [
    'login.php' => 'login',
    'admin/dashboard.php' => 'admin/dashboard',
    'admin/clients.php' => 'admin/clients',
    'admin/commandes.php' => 'admin/commandes',
    'admin/employes.php' => 'admin/employes',
    'admin/logs.php' => 'admin/logs',
    'admin/parametres.php' => 'admin/parametres',
    'admin/plats.php' => 'admin/plats',
    'admin/rapports.php' => 'admin/rapports',
    'admin/tables.php' => 'admin/tables',
    'client/index.php' => 'client/index',
    'client/identite.php' => 'client/identite',
    'client/menu.php' => 'client/menu',
    'client/panier.php' => 'client/panier',
    'client/confirmation.php' => 'client/confirmation',
    'client/confirmation_paiement.php' => 'client/confirmation_paiement',
    'client/confirmation_success.php' => 'client/confirmation_success',
    'client/paiement_client.php' => 'client/paiement_client',
    'client/suivi_commande.php' => 'client/suivi_commande',
    'client/mes_commandes.php' => 'client/mes_commandes',
    'cuisine/dashboard.php' => 'cuisine/dashboard',
    'cuisine/commandes.php' => 'cuisine/commandes',
    'cuisine/parametres.php' => 'cuisine/parametres',
    'manager/dashboard.php' => 'manager/dashboard',
    'manager/commandes.php' => 'manager/commandes',
    'manager/parametres.php' => 'manager/parametres',
    'caissier/commandes.php' => 'caissier/commandes',
    'caissier/generer_facture.php' => 'caissier/generer_facture',
    'caissier/paiement.php' => 'caissier/paiement',
    'caissier/parametres.php' => 'caissier/parametres',
    'caissier/rapports.php' => 'caissier/rapports',
];

$actionEntries = [
    'admin/contact.php',
    'admin/rapport_export.php',
    'admin/rapport_imprimer.php',
    'client/cart_key.php',
    'client/clear_session.php',
    'client/commande.php',
    'client/get_cart_count.php',
    'client/nouvelle_commande.php',
    'client/traitement_paiement.php',
    'caissier/rapport_export.php',
    'caissier/rapport_imprimer.php',
    'api/client/commande_statut.php',
    'api/commande/commande.php',
    'api/employe/employe.php',
    'api/menu/menu.php',
    'api/paiement/paiement.php',
    'api/stats/stats.php',
    'logout.php',
];

function extractUses(string $src): array
{
    preg_match_all('/^use\s+[^;]+;/m', $src, $m);

    return array_values(array_unique(array_filter(
        $m[0],
        static fn (string $u): bool => !str_contains($u, 'App\\Http\\Kernel')
            && !str_contains($u, 'App\\Http\\FrontController')
    )));
}

function splitViewBody(string $src): ?string
{
    // Après Kernel::forFile + extract optionnel
    if (preg_match(
        '/Kernel::forFile\(__FILE__\);\s*(?:if\s*\(\s*\$result\s*!==\s*null\s*\)\s*\{\s*extract\(\$result,\s*EXTR_SKIP\);\s*\}\s*)?/s',
        $src,
        $m,
        PREG_OFFSET_CAPTURE
    )) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $body = ltrim(substr($src, $pos));
        if (str_starts_with($body, '?>')) {
            $body = ltrim(substr($body, 2));
        }

        return $body;
    }

    return null;
}

function writeEntry(string $path, bool $rootLevel): void
{
    $boot = $rootLevel
        ? "__DIR__ . '/bootstrap/app.php'"
        : "__DIR__ . '/../bootstrap/app.php'";

    $content = <<<PHP
<?php

declare(strict_types=1);

require_once {$boot};

use App\\Http\\FrontController;

FrontController::run(__FILE__);

PHP;

    file_put_contents($path, $content);
}

function ensureDir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("Impossible de créer {$dir}");
    }
}

$migrated = 0;
$skipped = 0;

foreach ($pages as $rel => $template) {
    $entry = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($entry)) {
        echo "SKIP missing: {$rel}\n";
        $skipped++;
        continue;
    }

    $src = file_get_contents($entry);
    if ($src === false) {
        throw new RuntimeException("Lecture impossible : {$rel}");
    }

    // Déjà migré
    if (str_contains($src, 'FrontController::run') && !str_contains($src, '<!DOCTYPE') && !str_contains($src, '<!doctype') && !str_contains($src, 'shellStart')) {
        echo "OK already: {$rel}\n";
        continue;
    }

    $body = splitViewBody($src);
    if ($body === null || trim($body) === '') {
        echo "FAIL split: {$rel}\n";
        $skipped++;
        continue;
    }

    $uses = extractUses($src);
    $tplPath = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR
        . 'View' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

    ensureDir(dirname($tplPath));

    $header = "<?php\n\ndeclare(strict_types=1);\n\n";
    if ($uses !== []) {
        $header .= implode("\n", $uses) . "\n\n";
    }

    $trimmedBody = ltrim($body);
    // PHP encore ouvert (AdminPage::shellStart, $x = …) vs markup HTML
    if (preg_match('/^(AdminPage|Dashboard|ClientPage|Application|\$|[A-Za-z_\\\\]+::)/', $trimmedBody)) {
        $tpl = $header . $trimmedBody;
    } else {
        $tpl = rtrim($header) . "\n?>\n" . $trimmedBody;
    }
    if (!str_ends_with($tpl, "\n")) {
        $tpl .= "\n";
    }

    file_put_contents($tplPath, $tpl);
    writeEntry($entry, !str_contains($rel, '/'));
    echo "MIGRATED {$rel} -> templates/{$template}.php\n";
    $migrated++;
}

foreach ($actionEntries as $rel) {
    $entry = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($entry)) {
        echo "SKIP missing action: {$rel}\n";
        continue;
    }
    $src = file_get_contents($entry);
    if ($src !== false && str_contains($src, 'FrontController::run')) {
        continue;
    }
    writeEntry($entry, !str_contains($rel, '/'));
    echo "THIN {$rel}\n";
}

// Injecter 'template' dans routes.php
$routesFile = $root . '/config/routes.php';
$routesSrc = file_get_contents($routesFile);
if ($routesSrc === false) {
    throw new RuntimeException('routes.php illisible');
}

foreach ($pages as $rel => $template) {
    $needle = "'{$rel}' => [";
    $pos = strpos($routesSrc, $needle);
    if ($pos === false) {
        echo "ROUTE miss: {$rel}\n";
        continue;
    }
    if (str_contains(substr($routesSrc, $pos, 400), "'template'")) {
        continue;
    }
    // Insérer après l'ouverture du tableau de route
    $insertAt = $pos + strlen($needle);
    $routesSrc = substr($routesSrc, 0, $insertAt)
        . "\n        'template' => '{$template}',"
        . substr($routesSrc, $insertAt);
    echo "ROUTE template {$rel}\n";
}

file_put_contents($routesFile, $routesSrc);

echo "\nDone. migrated={$migrated} skipped={$skipped}\n";
