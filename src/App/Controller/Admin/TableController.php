<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Repository\TableRepository;
use App\Service\QrService;
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
                    $code = QrService::generateTableCode($next);
                    $num = $this->tables->create($places, $libelle !== '' ? $libelle : null, $code);
                    $message = "Table n°{$num} créée avec QR.";
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }

            if (isset($post['toggle_actif'])) {
                $this->tables->toggleActif((int) $post['num_table']);
                $message = 'Statut de la table mis à jour.';
            }

            if (isset($post['regenerate_code'])) {
                $num = (int) $post['num_table'];
                $code = QrService::generateTableCode($num);
                $this->tables->updateCode($num, $code);
                $message = "Nouveau QR généré pour la table {$num}.";
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

    /**
     * @return array{
     *   stickers: list<array{num_table:int,label:string,places:int,code:string,url:string,qr_img:string,actif:bool}>,
     *   printAll: bool
     * }|null
     */
    public function printStickers(array $get): ?array
    {
        $this->tables->ensureSchema();
        $this->tables->assignMissingCodes();

        $qr = Application::getInstance()->qrService();
        $all = isset($get['all']);
        $numTable = isset($get['table']) ? (int) $get['table'] : 0;

        if (!$all && $numTable <= 0) {
            return null;
        }

        $rows = $this->tables->findAll();
        if (!$all) {
            $rows = array_values(array_filter(
                $rows,
                static fn(array $row): bool => (int) ($row['num_table'] ?? 0) === $numTable
            ));
        }

        if ($rows === []) {
            return null;
        }

        $stickers = [];
        foreach ($rows as $t) {
            $code = trim((string) ($t['code_table'] ?? ''));
            if ($code === '') {
                continue;
            }

            $num = (int) $t['num_table'];
            $libelle = trim((string) ($t['libelle'] ?? ''));
            $label = $libelle !== '' ? $libelle : ('Table ' . $num);
            $url = $qr->tableEntryUrl($code);

            $stickers[] = [
                'num_table' => $num,
                'label' => $label,
                'places' => (int) ($t['nombre_place'] ?? 0),
                'code' => $code,
                'url' => $url,
                'qr_img' => QrService::printImageUrl($url),
                'actif' => (bool) (int) ($t['actif'] ?? 0),
            ];
        }

        if ($stickers === []) {
            return null;
        }

        return [
            'stickers' => $stickers,
            'printAll' => $all,
        ];
    }
}
