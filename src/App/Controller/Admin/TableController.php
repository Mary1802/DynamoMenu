<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Repository\TableRepository;
use App\Service\TableCodeService;
use PDOException;

final class TableController
{
    private TableRepository $tables;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->tables = $app->tableRepository();
    }

    /**
     * @param array<string, mixed> $post
     * @return array{message: string, error: string, tables: list<array<string,mixed>>}
     */
    public function handle(array $post): array
    {
        $this->tables->ensureSchema();
        $this->tables->assignMissingCodes();

        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($post['create_table'])) {
                try {
                    $places = max(1, (int) ($post['nombre_place'] ?? 2));
                    $libelle = trim((string) ($post['libelle'] ?? ''));
                    $next = (int) Application::getInstance()->db()->query(
                        'SELECT COALESCE(MAX(num_table), 0) + 1 FROM table_restaurant'
                    )->fetchColumn();
                    $code = TableCodeService::generateTableCode($next);
                    $num = $this->tables->create($places, $libelle !== '' ? $libelle : null, $code);
                    $message = "Table n°{$num} créée.";
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }

            if (isset($post['toggle_actif'])) {
                $this->tables->toggleActif((int) $post['num_table']);
                $message = 'Statut de la table mis à jour.';
            }

            if (isset($post['update_table'])) {
                $num = (int) $post['num_table'];
                $places = max(1, (int) ($post['nombre_place'] ?? 2));
                $libelle = trim((string) ($post['libelle'] ?? ''));
                $this->tables->update($num, $places, $libelle !== '' ? $libelle : null);
                $message = "Table n°{$num} modifiée.";
            }

            if (isset($post['delete_table'])) {
                $num = (int) $post['num_table'];
                try {
                    if ($this->tables->countCommandes($num) > 0) {
                        $error = "Impossible de supprimer la table n°{$num} : des commandes y sont encore rattachées.";
                    } else {
                        $this->tables->delete($num);
                        $message = "Table n°{$num} supprimée.";
                    }
                } catch (PDOException $e) {
                    $error = 'Suppression impossible : ' . $e->getMessage();
                }
            }
        }

        return [
            'message' => $message,
            'error' => $error,
            'tables' => $this->tables->findAll(),
        ];
    }
}
