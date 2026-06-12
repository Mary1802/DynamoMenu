<?php

/**
 * Contexte table (scan QR) — ponts vers TableContextService / TableRepository.
 */

require_once __DIR__ . '/bootstrap.php';

function table_ensure_schema(PDO $pdo): void
{
    app()->tableRepository()->ensureSchema();
}

function table_assign_missing_codes(PDO $pdo): void
{
    app()->tableRepository()->assignMissingCodes();
}

/** @return array<string, mixed>|null */
function table_find_by_code(PDO $pdo, string $code): ?array
{
    return app()->tableRepository()->findByCode($code);
}

function bootstrap_table_context(PDO $pdo): void
{
    app()->tableContextService()->bootstrap();
}

/** @return array{num_table:int,code_table:?string,label:string}|null */
function table_session(): ?array
{
    return app()->tableContextService()->session();
}

function table_link(string $path): string
{
    return app()->tableContextService()->link($path);
}

function table_redirect_after_scan(string $target = 'index.php'): void
{
    app()->tableContextService()->redirectAfterScan($target);
}

function table_require_or_redirect(string $redirect = 'index.php'): void
{
    app()->tableContextService()->requireOrRedirect($redirect);
}
