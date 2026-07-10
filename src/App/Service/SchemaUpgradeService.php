<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Support\MenuImageIndex;
use PDO;
use PDOException;

final class SchemaUpgradeService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Application $app,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db(), $app);
    }

    public function run(): void
    {
        $this->ensureContientSchema();
        $this->ensureNotificationTable();
        $this->ensureCommandeColumns();
        $this->ensurePlatColumns();
        $this->ensureBoissonColumns();
        $this->ensureTypeBoisson();
        $this->ensureEmployeRoleEnum();
        $this->app->horairesRepository()->ensureTable();
        $this->normalizeMenuImages();
        $this->app->menuService()->seedStaticItems();
        $this->app->employePasswordService()->upgradePlaintextPasswords();
    }

    private function ensureContientSchema(): void
    {
        $cols = array_column($this->pdo->query('SHOW COLUMNS FROM contient')->fetchAll(PDO::FETCH_ASSOC), 'Field');

        if (!in_array('sauces', $cols, true)) {
            $this->pdo->exec("ALTER TABLE contient ADD COLUMN sauces VARCHAR(255) NOT NULL DEFAULT '' AFTER sous_total");
            $cols[] = 'sauces';
        }
        if (!in_array('personnalisation_boisson', $cols, true)) {
            $after = in_array('sauces', $cols, true) ? 'sauces' : 'sous_total';
            $this->pdo->exec("ALTER TABLE contient ADD COLUMN personnalisation_boisson VARCHAR(255) NOT NULL DEFAULT '' AFTER {$after}");
        }
    }

    private function ensureNotificationTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS notification (
            id_notification INT PRIMARY KEY AUTO_INCREMENT,
            id_client INT NULL,
            num_commande INT NULL,
            canal ENUM('in_app', 'email') NOT NULL DEFAULT 'in_app',
            type_notification ENUM('commande', 'fidelite', 'promo', 'systeme') NOT NULL DEFAULT 'commande',
            titre VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            date_envoi TIMESTAMP NULL,
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE SET NULL,
            FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE SET NULL
        )");
    }

    private function ensureCommandeColumns(): void
    {
        $commandeCols = array_column($this->pdo->query('SHOW COLUMNS FROM commande')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (!in_array('instructions_speciales', $commandeCols, true)) {
            $after = in_array('mode_paiement_souhaite', $commandeCols, true) ? 'mode_paiement_souhaite' : 'montant_total';
            $this->pdo->exec("ALTER TABLE commande ADD COLUMN instructions_speciales TEXT NULL AFTER {$after}");
            $commandeCols[] = 'instructions_speciales';
        }
        if (!in_array('date_debut_preparation', $commandeCols, true)) {
            $after = in_array('instructions_speciales', $commandeCols, true) ? 'instructions_speciales' : 'statut';
            $this->pdo->exec("ALTER TABLE commande ADD COLUMN date_debut_preparation DATETIME NULL AFTER {$after}");
            $commandeCols[] = 'date_debut_preparation';
        }
        if (!in_array('temps_preparation_estime_sec', $commandeCols, true)) {
            $after = in_array('date_debut_preparation', $commandeCols, true) ? 'date_debut_preparation' : 'statut';
            $this->pdo->exec("ALTER TABLE commande ADD COLUMN temps_preparation_estime_sec INT UNSIGNED NULL AFTER {$after}");
        }
    }

    private function ensurePlatColumns(): void
    {
        $platCols = array_column($this->pdo->query('SHOW COLUMNS FROM plat')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (!in_array('prix_unitaire', $platCols, true)) {
            if (in_array('categorie', $platCols, true)) {
                $this->pdo->exec("ALTER TABLE plat ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER nom_plat");
            } else {
                $this->pdo->exec("ALTER TABLE plat ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0");
            }
            $platCols[] = 'prix_unitaire';
        }
        if (!in_array('image_url', $platCols, true)) {
            $this->pdo->exec("ALTER TABLE plat ADD COLUMN image_url VARCHAR(255) NULL AFTER categorie");
        }
        if (!in_array('quantite_plat', $platCols, true)) {
            $this->pdo->exec("ALTER TABLE plat ADD COLUMN quantite_plat INT NOT NULL DEFAULT 0 AFTER categorie");
            $platCols[] = 'quantite_plat';
        }
        if (!in_array('temps_preparation_min', $platCols, true)) {
            $after = in_array('quantite_plat', $platCols, true) ? 'quantite_plat' : 'categorie';
            $this->pdo->exec("ALTER TABLE plat ADD COLUMN temps_preparation_min INT UNSIGNED NOT NULL DEFAULT 15 AFTER {$after}");
        }
    }

    private function ensureEmployeRoleEnum(): void
    {
        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM employe LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
            if (!$col || !is_string($col['Type'] ?? null)) {
                return;
            }
            if (!str_contains($col['Type'], "'manager'")) {
                $this->pdo->exec("ALTER TABLE employe MODIFY role ENUM('admin', 'cuisinier', 'caissier', 'manager') NOT NULL");
            }
        } catch (PDOException) {
            // Table employe absente (installation incomplète).
        }
    }

    private function ensureBoissonColumns(): void
    {
        $boissonCols = array_column($this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (!in_array('id_type', $boissonCols, true)) {
            $this->pdo->exec(in_array('type_boisson', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN id_type INT NULL AFTER type_boisson"
                : "ALTER TABLE boisson ADD COLUMN id_type INT NULL");
            $boissonCols[] = 'id_type';
        }
        if (!in_array('dosage', $boissonCols, true)) {
            $this->pdo->exec(in_array('type_boisson', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN dosage VARCHAR(100) NULL AFTER type_boisson"
                : "ALTER TABLE boisson ADD COLUMN dosage VARCHAR(100) NULL");
            $boissonCols[] = 'dosage';
        }
        if (!in_array('quantite_boisson', $boissonCols, true)) {
            $this->pdo->exec(in_array('dosage', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN quantite_boisson INT DEFAULT 0 AFTER dosage"
                : "ALTER TABLE boisson ADD COLUMN quantite_boisson INT DEFAULT 0");
            $boissonCols[] = 'quantite_boisson';
        }
        if (!in_array('options_fruits', $boissonCols, true)) {
            $this->pdo->exec(in_array('quantite_boisson', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) NULL AFTER quantite_boisson"
                : "ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) NULL");
            $boissonCols[] = 'options_fruits';
        }
        if (!in_array('prix_unitaire', $boissonCols, true)) {
            $this->pdo->exec(in_array('quantite_boisson', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite_boisson"
                : "ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0");
        }
        if (!in_array('image_url', $boissonCols, true)) {
            $this->pdo->exec(in_array('options_fruits', $boissonCols, true)
                ? "ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL AFTER options_fruits"
                : "ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL");
        }
    }

    private function ensureTypeBoisson(): void
    {
        $typeBoissonExists = count($this->pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)) > 0;
        if ($typeBoissonExists) {
            $typeBoissonCols = array_column($this->pdo->query('SHOW COLUMNS FROM type_boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (in_array('id_boisson', $typeBoissonCols, true) && !in_array('id_type', $typeBoissonCols, true)) {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS type_boisson_new (
                    id_type INT PRIMARY KEY AUTO_INCREMENT,
                    nom_type VARCHAR(100) NOT NULL UNIQUE
                )");
                $this->pdo->exec("INSERT IGNORE INTO type_boisson_new (nom_type) SELECT DISTINCT nom_type FROM type_boisson");
                $this->pdo->exec('DROP TABLE type_boisson');
                $this->pdo->exec('RENAME TABLE type_boisson_new TO type_boisson');
            }
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS type_boisson (
            id_type INT PRIMARY KEY AUTO_INCREMENT,
            nom_type VARCHAR(100) NOT NULL UNIQUE
        )");
        $this->pdo->exec("INSERT IGNORE INTO type_boisson (id_type, nom_type) VALUES (1, 'soda')");
        $this->pdo->exec('UPDATE boisson SET id_type = 1');

        $boissonCols = array_column($this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (in_array('type_boisson', $boissonCols, true)) {
            try {
                $this->pdo->exec('ALTER TABLE boisson DROP COLUMN type_boisson');
            } catch (PDOException) {
                // ignore
            }
        }

        try {
            $this->pdo->exec('ALTER TABLE boisson ADD CONSTRAINT fk_boisson_type_boisson FOREIGN KEY (id_type) REFERENCES type_boisson(id_type) ON DELETE SET NULL');
        } catch (PDOException) {
            // ignore
        }
    }

    private function normalizeMenuImages(): void
    {
        try {
            $tables = ['plat' => 'id_plat', 'boisson' => 'id_boisson'];
            foreach ($tables as $tableName => $idCol) {
                if ($this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote($tableName))->fetchColumn() === false) {
                    continue;
                }
                $cols = array_column($this->pdo->query('SHOW COLUMNS FROM ' . $tableName)->fetchAll(PDO::FETCH_ASSOC), 'Field');
                if (!in_array('image_url', $cols, true)) {
                    continue;
                }
                if ($tableName === 'plat' && in_array('categorie', $cols, true)) {
                    $this->pdo->exec("UPDATE plat SET categorie = 'Combo' WHERE categorie = 'Kombo'");
                }
                $this->pdo->exec(
                    "UPDATE {$tableName} SET image_url = REPLACE(REPLACE(image_url, 'images/kombo/', 'images/combo/'), 'images/Kombo/', 'images/combo/')
                     WHERE image_url LIKE '%images/kombo/%' OR image_url LIKE '%images/Kombo/%'"
                );
                $stmt = $this->pdo->query("SELECT {$idCol}, image_url FROM {$tableName} WHERE image_url IS NOT NULL AND image_url <> ''");
                $update = $this->pdo->prepare("UPDATE {$tableName} SET image_url = ? WHERE {$idCol} = ?");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $normalized = MenuImageIndex::normalizePath((string) $row['image_url']);
                    if ($normalized !== null && $normalized !== $row['image_url']) {
                        $update->execute([$normalized, (int) $row[$idCol]]);
                    }
                }
            }
        } catch (PDOException) {
            // ignore
        }
    }
}
