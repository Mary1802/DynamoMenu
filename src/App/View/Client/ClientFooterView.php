<?php



declare(strict_types=1);



namespace App\View\Client;



use App\Core\Application;

use App\View\View;



final class ClientFooterView

{

    public static function render(): void

    {

        View::render('client/footer', [

            'year' => (int) date('Y'),

            'homeHref' => Application::getInstance()->tableContextService()->link('index.php'),

        ]);

    }

}


