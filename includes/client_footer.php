<?php

/**
 * Pied de page client — pont vers App\View\Client\ClientFooterView.
 */

require_once __DIR__ . '/bootstrap.php';

use App\View\Client\ClientFooterView;

function render_client_footer(): void
{
    ClientFooterView::render();
}
