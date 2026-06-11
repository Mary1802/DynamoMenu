<?php

/**
 * Contexte table (scan QR) — session client.
 */

function table_ensure_schema(PDO $pdo): void
{
    $columns = array_column($pdo->query('SHOW COLUMNS FROM table_restaurant')->fetchAll(PDO::FETCH_ASSOC), 'Field');

    if (!in_array('code_table', $columns, true)) {
        $pdo->exec("ALTER TABLE table_restaurant ADD COLUMN code_table VARCHAR(32) NULL UNIQUE AFTER num_table");
    }
    if (!in_array('actif', $columns, true)) {
        $pdo->exec('ALTER TABLE table_restaurant ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1');
    }
    if (!in_array('libelle', $columns, true)) {
        $pdo->exec('ALTER TABLE table_restaurant ADD COLUMN libelle VARCHAR(100) NULL AFTER nombre_place');
    }

    $commandeCols = array_column($pdo->query('SHOW COLUMNS FROM commande')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('mode_paiement_souhaite', $commandeCols, true)) {
        $pdo->exec("ALTER TABLE commande ADD COLUMN mode_paiement_souhaite ENUM('especes','mobile_money') NULL AFTER montant_total");
    }
    if (!in_array('instructions_speciales', $commandeCols, true)) {
        $pdo->exec('ALTER TABLE commande ADD COLUMN instructions_speciales TEXT NULL AFTER mode_paiement_souhaite');
    }
}

function table_assign_missing_codes(PDO $pdo): void
{
    require_once dirname(__DIR__) . '/services/qr_service.php';

    $rows = $pdo->query("SELECT num_table FROM table_restaurant WHERE code_table IS NULL OR code_table = ''")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('UPDATE table_restaurant SET code_table = ? WHERE num_table = ?');

    foreach ($rows as $row) {
        $code = qr_generate_table_code((int) $row['num_table']);
        $stmt->execute([$code, $row['num_table']]);
    }
}

function table_find_by_code(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare('
        SELECT num_table, code_table, nombre_place, libelle, actif
        FROM table_restaurant
        WHERE code_table = ?
        LIMIT 1
    ');
    $stmt->execute([trim($code)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !(int) $row['actif']) {
        return null;
    }

    return $row;
}

/**
 * Lit ?t= ou ?table= et enregistre la table en session.
 */
function bootstrap_table_context(PDO $pdo): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    table_ensure_schema($pdo);
    table_assign_missing_codes($pdo);

    $code = trim((string) ($_GET['t'] ?? $_GET['table'] ?? ''));
    if ($code === '') {
        return;
    }

    $table = table_find_by_code($pdo, $code);
    if (!$table) {
        $_SESSION['table_error'] = 'QR code invalide ou table désactivée.';
        return;
    }

    unset($_SESSION['table_error']);
    $_SESSION['table_code'] = $table['code_table'];
    $_SESSION['num_table'] = (int) $table['num_table'];
    $_SESSION['table_label'] = $table['libelle'] ?: ('Table ' . $table['num_table']);
}

function table_session(): ?array
{
    if (empty($_SESSION['num_table'])) {
        return null;
    }

    return [
        'num_table' => (int) $_SESSION['num_table'],
        'code_table' => $_SESSION['table_code'] ?? null,
        'label' => $_SESSION['table_label'] ?? ('Table ' . $_SESSION['num_table']),
    ];
}

/**
 * Ajoute ?t=CODE à un lien client (secours si la session expire sur mobile).
 */
function table_link(string $path): string
{
    $ctx = table_session();
    if (!$ctx || empty($ctx['code_table'])) {
        return $path;
    }

    $sep = str_contains($path, '?') ? '&' : '?';

    return $path . $sep . 't=' . rawurlencode((string) $ctx['code_table']);
}

/**
 * Après scan QR réussi : URL propre sans ?t= (la table reste en session).
 */
function table_redirect_after_scan(string $target = 'index.php'): void
{
    $code = trim((string) ($_GET['t'] ?? $_GET['table'] ?? ''));
    if ($code === '' || !table_session()) {
        return;
    }

    header('Location: ' . $target);
    exit;
}

function table_require_or_redirect(string $redirect = 'index.php'): void
{
    if (!table_session()) {
        header('Location: ' . $redirect . '?err=table');
        exit;
    }
}
