<?php



declare(strict_types=1);



namespace App\Service;



use App\Auth\ClientSessionService;

use App\Core\Application;

use App\Repository\TableRepository;



final class TableContextService

{

    public function __construct(

        private readonly TableRepository $tables,

        private readonly ClientSessionService $session,

    ) {

    }



    public static function fromApp(?Application $app = null): self

    {

        $app ??= Application::getInstance();



        return new self($app->tableRepository(), $app->clientSession());

    }



    public function bootstrap(): void

    {

        $this->session->start();

        $this->tables->ensureSchema();

        $this->tables->assignMissingCodes();



        $code = trim((string) ($_GET['t'] ?? $_GET['table'] ?? ''));

        if ($code !== '') {
            $table = $this->tables->findByCode($code);
            if ($table) {
                $this->bindTable($table);

                return;
            }

            if (!empty($_SESSION['num_table'])) {
                return;
            }

            $_SESSION['table_error'] = 'Table introuvable ou désactivée.';
        }

        if (!empty($_SESSION['num_table'])) {
            return;
        }

        $this->bindTable($this->tables->ensureDefaultTable(1, 4, 'Table 1'));
    }

    /** @param array<string, mixed> $table */
    private function bindTable(array $table): void
    {
        unset($_SESSION['table_error']);
        $_SESSION['table_code'] = $table['code_table'];
        $_SESSION['num_table'] = (int) $table['num_table'];
        $_SESSION['table_label'] = $table['libelle'] ?: ('Table ' . $table['num_table']);
    }



    /** @return array{num_table:int,code_table:?string,label:string}|null */

    public function session(): ?array

    {

        $this->session->start();



        if (empty($_SESSION['num_table'])) {

            return null;

        }



        return [

            'num_table' => (int) $_SESSION['num_table'],

            'code_table' => $_SESSION['table_code'] ?? null,

            'label' => $_SESSION['table_label'] ?? ('Table ' . $_SESSION['num_table']),

        ];

    }



    public function link(string $path): string

    {

        $ctx = $this->session();

        if (!$ctx || empty($ctx['code_table'])) {

            return $path;

        }



        $sep = str_contains($path, '?') ? '&' : '?';



        return $path . $sep . 't=' . rawurlencode((string) $ctx['code_table']);
    }

    /**
     * @param array<string, int|string|null> $params
     */
    public function linkWithQuery(string $path, array $params = []): string
    {
        $url = $this->link($path);
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return $url;
    }

    public function redirectAfterTableBind(string $target = 'index.php'): void
    {
        $code = trim((string) ($_GET['t'] ?? $_GET['table'] ?? ''));
        if ($code === '' || !$this->session()) {
            return;
        }

        // La table est déjà en session : retirer ?t= de l'URL pour éviter une boucle de redirection.
        header('Location: ' . $target);
        exit;
    }



    public function requireOrRedirect(string $redirect = 'index.php'): void

    {

        if (!$this->session()) {

            header('Location: ' . $redirect . '?err=table');

            exit;

        }

    }



    public function redirectToIndex(): never

    {

        header('Location: ' . $this->link('index.php'), true, 302);

        exit;

    }



    public function consumeTableError(): ?string

    {

        $this->session->start();

        $error = $_SESSION['table_error'] ?? null;

        unset($_SESSION['table_error']);



        return is_string($error) ? $error : null;

    }

}


