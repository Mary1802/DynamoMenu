<?php



declare(strict_types=1);



namespace App\Repository;



use App\Service\TableCodeService;

use PDO;

use PDOException;



final class TableRepository extends BaseRepository

{

    public function ensureSchema(): void

    {

        $columns = $this->columnNames('table_restaurant');



        if (!in_array('code_table', $columns, true)) {

            $this->pdo->exec(

                'ALTER TABLE table_restaurant ADD COLUMN code_table VARCHAR(32) NULL UNIQUE AFTER num_table'

            );

        }

        if (!in_array('actif', $columns, true)) {

            $this->pdo->exec('ALTER TABLE table_restaurant ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1');

        }

        if (!in_array('libelle', $columns, true)) {

            $this->pdo->exec('ALTER TABLE table_restaurant ADD COLUMN libelle VARCHAR(100) NULL AFTER nombre_place');

        }



        $commandeCols = $this->columnNames('commande');

        if (!in_array('mode_paiement_souhaite', $commandeCols, true)) {

            $this->pdo->exec(

                "ALTER TABLE commande ADD COLUMN mode_paiement_souhaite ENUM('especes','mobile_money') NULL AFTER montant_total"

            );

        }

        if (!in_array('instructions_speciales', $commandeCols, true)) {

            $this->pdo->exec('ALTER TABLE commande ADD COLUMN instructions_speciales TEXT NULL AFTER mode_paiement_souhaite');

        }

    }



    public function assignMissingCodes(): void

    {

        $rows = $this->pdo->query(

            "SELECT num_table FROM table_restaurant WHERE code_table IS NULL OR code_table = ''"

        )->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('UPDATE table_restaurant SET code_table = ? WHERE num_table = ?');



        foreach ($rows as $row) {

            $code = TableCodeService::generateTableCode((int) $row['num_table']);

            $stmt->execute([$code, $row['num_table']]);

        }

    }



    /** @return array<string, mixed>|null */
    public function findByNumTable(int $numTable): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT num_table, code_table, nombre_place, libelle, actif
            FROM table_restaurant
            WHERE num_table = ?
            LIMIT 1
        ');
        $stmt->execute([$numTable]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed> */
    public function ensureDefaultTable(int $numTable, int $places, ?string $libelle = null): array
    {
        $existing = $this->findByNumTable($numTable);
        if ($existing !== null) {
            if ((int) $existing['actif'] !== 1) {
                $stmt = $this->pdo->prepare('UPDATE table_restaurant SET actif = 1 WHERE num_table = ?');
                $stmt->execute([$numTable]);
                $existing['actif'] = 1;
            }

            if ((int) $existing['nombre_place'] !== $places) {
                $stmt = $this->pdo->prepare('UPDATE table_restaurant SET nombre_place = ? WHERE num_table = ?');
                $stmt->execute([$places, $numTable]);
                $existing['nombre_place'] = $places;
            }

            if (($existing['code_table'] ?? '') === '') {
                $code = TableCodeService::generateTableCode($numTable);
                $this->updateCode($numTable, $code);
                $existing['code_table'] = $code;
            }

            return $existing;
        }

        $code = TableCodeService::generateTableCode($numTable);
        $label = $libelle ?? ('Table ' . $numTable);
        $stmt = $this->pdo->prepare(
            'INSERT INTO table_restaurant (num_table, nombre_place, libelle, code_table, actif) VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$numTable, $places, $label, $code]);

        return $this->findByNumTable($numTable) ?? [
            'num_table' => $numTable,
            'nombre_place' => $places,
            'libelle' => $label,
            'code_table' => $code,
            'actif' => 1,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code): ?array

    {

        $stmt = $this->pdo->prepare('

            SELECT num_table, code_table, nombre_place, libelle, actif

            FROM table_restaurant

            WHERE code_table = ?

            LIMIT 1

        ');

        $stmt->execute([trim($code)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);



        if (!$row || !(int) $row['actif']) {

            return null;

        }



        return $row;

    }



    /** @return list<array<string, mixed>> */

    public function findAll(): array

    {

        return $this->pdo->query('SELECT * FROM table_restaurant ORDER BY num_table')->fetchAll(PDO::FETCH_ASSOC);

    }



    public function create(int $places, ?string $libelle, string $code): int

    {

        $next = (int) $this->pdo->query('SELECT COALESCE(MAX(num_table), 0) + 1 FROM table_restaurant')->fetchColumn();

        $stmt = $this->pdo->prepare(

            'INSERT INTO table_restaurant (num_table, nombre_place, libelle, code_table, actif) VALUES (?, ?, ?, ?, 1)'

        );

        $stmt->execute([$next, $places, $libelle, $code]);



        return $next;

    }



    public function toggleActif(int $numTable): void

    {

        $stmt = $this->pdo->prepare(

            'UPDATE table_restaurant SET actif = IF(actif = 1, 0, 1) WHERE num_table = ?'

        );

        $stmt->execute([$numTable]);

    }



    public function updateCode(int $numTable, string $code): void

    {

        $stmt = $this->pdo->prepare('UPDATE table_restaurant SET code_table = ? WHERE num_table = ?');

        $stmt->execute([$code, $numTable]);

    }



    public function update(int $numTable, int $places, ?string $libelle): void

    {

        $stmt = $this->pdo->prepare(

            'UPDATE table_restaurant SET nombre_place = ?, libelle = ? WHERE num_table = ?'

        );

        $stmt->execute([$places, $libelle, $numTable]);

    }



    public function countCommandes(int $numTable): int

    {

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM commande WHERE num_table = ?');

        $stmt->execute([$numTable]);



        return (int) $stmt->fetchColumn();

    }



    public function delete(int $numTable): void

    {

        $stmt = $this->pdo->prepare('DELETE FROM table_restaurant WHERE num_table = ?');

        $stmt->execute([$numTable]);

    }



    /** @return list<string> */

    private function columnNames(string $table): array

    {

        try {

            return array_column(

                $this->pdo->query('SHOW COLUMNS FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC),

                'Field'

            );

        } catch (PDOException) {

            return [];

        }

    }

}


