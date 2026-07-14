<?php



declare(strict_types=1);



namespace App\Http;



/**

 * Point d'entrée unique OOP pour les scripts publics (.php).

 * Chaque fichier d'entrée doit uniquement appeler FrontController::run(__FILE__).

 */

final class FrontController

{

    public static function run(string $entryFile): void

    {

        Kernel::forFile($entryFile);

    }

}


