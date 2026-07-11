<?php



declare(strict_types=1);



namespace App\View\Client;



use App\Controller\Client\OrderHistoryController;
use App\Core\Application;

use App\View\View;



final class ClientNavView

{

    public static function nav(string $active = ''): void

    {

        $tables = Application::getInstance()->tableContextService();
        Application::getInstance()->clientSession()->start();
        $hasOrders = OrderHistoryController::sessionOrderIds() !== [];

        View::render('client/nav', [
            'active' => $active,
            'tableCtx' => $tables->session(),
            'tableLink' => static fn (string $path): string => $tables->link($path),
            'hasOrders' => $hasOrders,
            'mesCommandesUrl' => $tables->link('mes_commandes.php'),
            'aboutHref' => $tables->link('index.php') . '#apropos',
        ]);

    }



    /** @param array{num_table:int,label:string,code_table?:string|null} $tableCtx */

    public static function tableWelcome(array $tableCtx): void

    {

        View::render('client/table-welcome', ['tableCtx' => $tableCtx]);

    }



    public static function tableError(string $message): void

    {

        View::render('client/table-error', ['message' => $message]);

    }



    /** @param array{num_table:int,label:string} $tableCtx */

    public static function tableStrip(array $tableCtx): void

    {

        View::render('client/table-strip', ['tableCtx' => $tableCtx]);

    }

}


