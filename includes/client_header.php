<?php

/**
 * Navigation client — pont vers App\View\Client\ClientNavView.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/table_context.php';

use App\View\Client\ClientNavView;

/** @param 'index'|'menu'|'panier'|'' $active */
function render_client_nav(string $active = ''): void
{
    ClientNavView::nav($active);
}

/** @param array{num_table:int,label:string,code_table?:string|null} $tableCtx */
function render_client_table_welcome(array $tableCtx): void
{
    ClientNavView::tableWelcome($tableCtx);
}

function render_client_table_error(string $message): void
{
    ClientNavView::tableError($message);
}

/** @param array{num_table:int,label:string} $tableCtx */
function render_client_table_strip(array $tableCtx): void
{
    ClientNavView::tableStrip($tableCtx);
}
