<?php



declare(strict_types=1);



namespace App\Controller\Client;



use App\Auth\ClientSessionService;

use App\Core\Application;

use App\Service\ClientProfileService;

use App\Service\TableContextService;



final class ClientIdentificationController

{

    private ClientSessionService $session;

    private TableContextService $tables;

    private ClientProfileService $profile;



    public function __construct(?Application $app = null)

    {

        $app ??= Application::getInstance();

        $this->session = $app->clientSession();

        $this->tables = $app->tableContextService();

        $this->profile = $app->clientProfileService();

    }



    /**

     * @return array{

     *   error: string|null,

     *   tableCtx: array<string,mixed>,

     *   nom: string,

     *   prenom: string,

     *   email: string,

     *   telephone: string,

     *   indexUrl: string

     * }|null

     */

    public function handle(array $post): ?array

    {

        $this->session->start();

        $this->tables->bootstrap();



        $tableCtx = $this->tables->session();

        if ($tableCtx === null) {

            header('Location: index.php?err=table');

            exit;

        }



        $this->profile->rejectIdentificationPageAccess();



        $error = null;

        $existing = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => ''];



        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['enregistrer_identite'])) {

            $this->session->verifyPostCsrf();



            $result = $this->profile->save(

                (string) ($post['nom'] ?? ''),

                (string) ($post['prenom'] ?? ''),

                (string) ($post['email'] ?? ''),

                (string) ($post['telephone'] ?? '')

            );



            if ($result['success']) {
                $returnTo = $this->profile->consumeReturnAfterIdentification();
                $redirect = $this->tables->link($returnTo);

                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Location: ' . $redirect, true, 303);

                exit;
            }



            $error = $result['error'];

            $existing = [

                'nom' => trim((string) ($post['nom'] ?? '')),

                'prenom' => trim((string) ($post['prenom'] ?? '')),

                'email' => trim((string) ($post['email'] ?? '')),

                'telephone' => trim((string) ($post['telephone'] ?? '')),

            ];

        }



        header('Cache-Control: no-store, no-cache, must-revalidate');

        header('Pragma: no-cache');



        $returnTo = $this->profile->peekReturnAfterIdentification();
        $isCheckout = $returnTo === 'confirmation.php';

        return [
            'error' => $error,
            'tableCtx' => $tableCtx,
            'nom' => $existing['nom'],
            'prenom' => $existing['prenom'],
            'email' => $existing['email'],
            'telephone' => $existing['telephone'],
            'indexUrl' => $this->tables->link('index.php'),
            'isCheckout' => $isCheckout,
        ];

    }

}


