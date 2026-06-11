<?php

/**
 * Pied de page client (copyright DynamoMenu).
 */
function render_client_footer(): void
{
    $year = (int) date('Y');
    $homeHref = 'index.php';
    if (function_exists('table_link')) {
        $homeHref = table_link('index.php');
    }
    ?>
    <footer class="client-footer" role="contentinfo">
        <div class="container-fluid px-4">
            <div class="client-footer-inner">
                <a class="client-footer-brand" href="<?php echo htmlspecialchars($homeHref); ?>">
                    Dynamo<span>Menu</span>
                </a>
                <p class="client-footer-copy">
                    &copy; <?php echo $year; ?> DynamoMenu. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>
    <?php
}
