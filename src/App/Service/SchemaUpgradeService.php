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
        $this->dropNotificationTable();
        $this->dropDemandePaiementTable();
        $this->ensureCommandeColumns();
        $this->ensurePlatColumns();
        $this->ensureBoissonColumns();
        $this->ensureTypeBoisson();
        $this->ensureEmployeRoleEnum();
        $this->app->clientRepository()->ensureSchema();
        $this->bootstrapStockIfEmpty();
        // Migrer contact.horaires → restaurant_horaires puis supprimer la colonne redondante.
        $this->app->contactRepository()->ensureSchema();
        $this->app->horairesRepository()->ensureTable();
        $this->normalizeMenuImages();
        $this->app->menuService()->seedStaticItems();
        $this->app->employePasswordService()->upgradePlaintextPasswords();
    }

    /** Initialise un stock de démarrage boissons si tout est encore à 0. */
    private function bootstrapStockIfEmpty(): void
    {
        try {
            $hasBoissonStock = (int) $this->pdo->query(
                'SELECT COUNT(*) FROM boisson WHERE quantite_boisson > 0'
            )->fetchColumn() > 0;

            if (!$hasBoissonStock) {
                $this->pdo->exec('UPDATE boisson SET quantite_boisson = 50 WHERE quantite_boisson = 0 OR quantite_boisson IS NULL');
            }
        } catch (PDOException) {
            // ignore
        }
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
            $cols[] = 'personnalisation_boisson';
        }

        $this->cleanupContientPersonnalisation();
    }

    /**
     * Ancien bug : le nom du plat/boisson était recopié dans personnalisation_boisson.
     * On ne conserve que le vrai choix (goût, etc.).
     */
    private function cleanupContientPersonnalisation(): void
    {
        try {
            // Plats : vider la colonne (le nom vient de plat.nom_plat).
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN plat p ON d.id_plat = p.id_plat
                SET d.personnalisation_boisson = ''
                WHERE d.id_plat IS NOT NULL
                  AND TRIM(d.personnalisation_boisson) <> ''
                  AND (
                    d.personnalisation_boisson = p.nom_plat
                    OR d.personnalisation_boisson LIKE CONCAT(p.nom_plat, ' — %')
                    OR d.personnalisation_boisson LIKE CONCAT(p.nom_plat, ' - %')
                  )
            ");

            // Boissons : ne garder que le goût après "Nom — goût".
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN boisson b ON d.id_boisson = b.id_boisson
                SET d.personnalisation_boisson = TRIM(SUBSTRING(d.personnalisation_boisson FROM CHAR_LENGTH(b.nom_boisson) + 4))
                WHERE d.id_boisson IS NOT NULL
                  AND d.personnalisation_boisson LIKE CONCAT(b.nom_boisson, ' — %')
            ");
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN boisson b ON d.id_boisson = b.id_boisson
                SET d.personnalisation_boisson = TRIM(SUBSTRING(d.personnalisation_boisson FROM CHAR_LENGTH(b.nom_boisson) + 3))
                WHERE d.id_boisson IS NOT NULL
                  AND d.personnalisation_boisson LIKE CONCAT(b.nom_boisson, ' - %')
            ");

            // Boissons : si la perso est juste le nom de la boisson, la vider.
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN boisson b ON d.id_boisson = b.id_boisson
                SET d.personnalisation_boisson = ''
                WHERE d.id_boisson IS NOT NULL
                  AND TRIM(d.personnalisation_boisson) = b.nom_boisson
            ");

            // Anciennes lignes orphelines : rattacher si le "perso" est en fait un nom de plat.
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN plat p ON TRIM(d.personnalisation_boisson) = p.nom_plat
                SET d.id_plat = p.id_plat, d.personnalisation_boisson = ''
                WHERE d.id_plat IS NULL AND d.id_boisson IS NULL
                  AND TRIM(d.personnalisation_boisson) <> ''
            ");
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN boisson b ON TRIM(d.personnalisation_boisson) = b.nom_boisson
                SET d.id_boisson = b.id_boisson, d.personnalisation_boisson = ''
                WHERE d.id_plat IS NULL AND d.id_boisson IS NULL
                  AND TRIM(d.personnalisation_boisson) <> ''
            ");

            // Orphelins "Nom boisson — goût" → rattacher + garder uniquement le goût.
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN boisson b ON d.personnalisation_boisson LIKE CONCAT(b.nom_boisson, ' — %')
                SET d.id_boisson = b.id_boisson,
                    d.personnalisation_boisson = TRIM(SUBSTRING(d.personnalisation_boisson FROM CHAR_LENGTH(b.nom_boisson) + 4))
                WHERE d.id_plat IS NULL AND d.id_boisson IS NULL
            ");
            $this->pdo->exec("
                UPDATE contient d
                INNER JOIN plat p ON d.personnalisation_boisson LIKE CONCAT(p.nom_plat, ' — %')
                SET d.id_plat = p.id_plat,
                    d.sauces = TRIM(SUBSTRING(d.personnalisation_boisson FROM CHAR_LENGTH(p.nom_plat) + 4)),
                    d.personnalisation_boisson = ''
                WHERE d.id_plat IS NULL AND d.id_boisson IS NULL
            ");
            // Lignes incohérentes : un article ne peut pas être plat ET boisson.
            $this->pdo->exec("
                UPDATE contient
                SET id_boisson = NULL
                WHERE id_plat IS NOT NULL AND id_plat > 0
                  AND id_boisson IS NOT NULL AND id_boisson > 0
            ");
        } catch (PDOException) {
            // ignore
        }
    }

    private function dropNotificationTable(): void
    {
        try {
            $this->pdo->exec('DROP TABLE IF EXISTS notification');
        } catch (PDOException) {
            // ignore
        }
    }

    private function dropDemandePaiementTable(): void
    {
        try {
            $this->pdo->exec('DROP TABLE IF EXISTS demande_paiement');
        } catch (PDOException) {
            // ignore
        }
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_NAME = ?
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                LIMIT 1
            ");
            $stmt->execute([$table, $constraintName]);
            if ($stmt->fetchColumn() === false) {
                // Peut exister sous un autre nom (MySQL auto) : chercher FK sur id_type.
                if ($table === 'boisson' && $constraintName === 'fk_boisson_type_boisson') {
                    $fks = $this->pdo->query("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'boisson'
                          AND COLUMN_NAME = 'id_type'
                          AND REFERENCED_TABLE_NAME = 'type_boisson'
                    ")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($fks as $fkName) {
                        $safe = str_replace('`', '``', (string) $fkName);
                        try {
                            $this->pdo->exec("ALTER TABLE boisson DROP FOREIGN KEY `{$safe}`");
                        } catch (PDOException) {
                            // ignore
                        }
                    }
                }

                return;
            }

            $safeTable = str_replace('`', '``', $table);
            $safeName = str_replace('`', '``', $constraintName);
            $this->pdo->exec("ALTER TABLE `{$safeTable}` DROP FOREIGN KEY `{$safeName}`");
        } catch (PDOException) {
            // ignore
        }
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
            $commandeCols[] = 'temps_preparation_estime_sec';
        }

        // Compteurs redondants (les quantités sont dans `contient`) + restes fidélité.
        foreach ([
            'quantite_plats',
            'quantite_boissons',
            'remise_montant',
            'id_recompense',
            'points_gagnes',
        ] as $obsolete) {
            if (!in_array($obsolete, $commandeCols, true)) {
                continue;
            }
            $this->dropCommandeColumn($obsolete);
        }

        $this->dropObsoleteFidelityTables();
    }

    private function dropCommandeColumn(string $column): void
    {
        try {
            // Si une FK pointe sur id_recompense, la retirer d'abord.
            if ($column === 'id_recompense') {
                $fks = $this->pdo->query("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'commande'
                      AND COLUMN_NAME = 'id_recompense'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($fks as $fkName) {
                    $fkName = (string) $fkName;
                    if ($fkName === '') {
                        continue;
                    }
                    $safe = str_replace('`', '``', $fkName);
                    try {
                        $this->pdo->exec("ALTER TABLE commande DROP FOREIGN KEY `{$safe}`");
                    } catch (PDOException) {
                        // ignore
                    }
                }
            }

            $safeCol = str_replace('`', '``', $column);
            $this->pdo->exec("ALTER TABLE commande DROP COLUMN `{$safeCol}`");
        } catch (PDOException) {
            // ignore
        }
    }

    private function dropObsoleteFidelityTables(): void
    {
        foreach (['historique_points', 'recompense_fidelite'] as $table) {
            try {
                $safe = str_replace('`', '``', $table);
                $this->pdo->exec("DROP TABLE IF EXISTS `{$safe}`");
            } catch (PDOException) {
                // ignore
            }
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
            $platCols[] = 'image_url';
        }
        if (!in_array('temps_preparation_min', $platCols, true)) {
            $after = in_array('categorie', $platCols, true) ? 'categorie' : null;
            $this->pdo->exec($after !== null
                ? "ALTER TABLE plat ADD COLUMN temps_preparation_min INT UNSIGNED NOT NULL DEFAULT 15 AFTER {$after}"
                : "ALTER TABLE plat ADD COLUMN temps_preparation_min INT UNSIGNED NOT NULL DEFAULT 15");
            $platCols[] = 'temps_preparation_min';
        }

        if (in_array('quantite_plat', $platCols, true)) {
            try {
                $this->pdo->exec('ALTER TABLE plat DROP COLUMN `quantite_plat`');
            } catch (PDOException) {
                // ignore
            }
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
            $after = in_array('quantite_boisson', $boissonCols, true) ? 'quantite_boisson' : 'dosage';
            $this->pdo->exec("ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) NULL DEFAULT '' AFTER {$after}");
            $boissonCols[] = 'options_fruits';
        }
        if (!in_array('prix_unitaire', $boissonCols, true)) {
            $after = in_array('options_fruits', $boissonCols, true)
                ? 'options_fruits'
                : (in_array('quantite_boisson', $boissonCols, true) ? 'quantite_boisson' : null);
            $this->pdo->exec($after !== null
                ? "ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER {$after}"
                : "ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0");
            $boissonCols[] = 'prix_unitaire';
        }
        if (!in_array('image_url', $boissonCols, true)) {
            $after = in_array('prix_unitaire', $boissonCols, true)
                ? 'prix_unitaire'
                : (in_array('options_fruits', $boissonCols, true) ? 'options_fruits' : null);
            $this->pdo->exec($after !== null
                ? "ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL AFTER {$after}"
                : "ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL");
            $boissonCols[] = 'image_url';
        }

        // Colonne legacy inutilisée (volume = dosage).
        if (in_array('format', $boissonCols, true)) {
            try {
                $this->pdo->exec('ALTER TABLE boisson DROP COLUMN `format`');
            } catch (PDOException) {
                // ignore
            }
        }

        $this->seedDefaultFruitOptions();
    }

    /** Remplit des goûts par défaut pour les boissons personnalisables encore vides. */
    private function seedDefaultFruitOptions(): void
    {
        $defaults = [
            'Jus de Fruit' => 'Orange,Banane,Pomme,Ananas,Mangue,Fraise',
            'Milkshake' => 'Orange,Banane,Pomme,Ananas,Mangue,Fraise',
            'Cocktail de Fruits' => 'Orange,Banane,Pomme,Ananas,Mangue,Fraise',
            'Smoothie Banane' => 'Banane,Fraise,Mangue,Ananas',
        ];

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE boisson SET options_fruits = ?
                 WHERE nom_boisson = ?
                   AND (options_fruits IS NULL OR TRIM(options_fruits) = '')"
            );
            foreach ($defaults as $name => $options) {
                $stmt->execute([$options, $name]);
            }
        } catch (PDOException) {
            // ignore
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

        foreach (['soda', 'eau', 'jus', 'alcool'] as $typeName) {
            $this->pdo->prepare('INSERT IGNORE INTO type_boisson (nom_type) VALUES (?)')->execute([$typeName]);
        }

        $this->mergeBoissonTypeAliases();

        $boissonCols = array_column($this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');

        // Migration ponctuelle depuis l'ancienne colonne ENUM, si encore présente.
        if (in_array('type_boisson', $boissonCols, true) && in_array('id_type', $boissonCols, true)) {
            try {
                $this->pdo->exec("
                    UPDATE boisson b
                    INNER JOIN type_boisson tb ON LOWER(TRIM(b.type_boisson)) = LOWER(TRIM(tb.nom_type))
                    SET b.id_type = tb.id_type
                    WHERE b.id_type IS NULL OR b.id_type = 0
                ");
            } catch (PDOException) {
                // ignore
            }
            try {
                $this->pdo->exec('ALTER TABLE boisson DROP COLUMN type_boisson');
            } catch (PDOException) {
                // ignore
            }
            $boissonCols = array_column($this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        }

        // Toute boisson doit avoir un type : combler les NULL puis imposer NOT NULL.
        if (in_array('id_type', $boissonCols, true)) {
            $sodaId = (int) ($this->pdo->query("SELECT id_type FROM type_boisson WHERE nom_type = 'soda' LIMIT 1")->fetchColumn() ?: 0);
            if ($sodaId > 0) {
                $stmt = $this->pdo->prepare('UPDATE boisson SET id_type = ? WHERE id_type IS NULL OR id_type = 0');
                $stmt->execute([$sodaId]);
            }

            $this->dropForeignKeyIfExists('boisson', 'fk_boisson_type_boisson');
            try {
                $this->pdo->exec('ALTER TABLE boisson MODIFY COLUMN id_type INT NOT NULL');
            } catch (PDOException) {
                // ignore
            }
            try {
                $this->pdo->exec(
                    'ALTER TABLE boisson ADD CONSTRAINT fk_boisson_type_boisson
                     FOREIGN KEY (id_type) REFERENCES type_boisson(id_type) ON DELETE RESTRICT'
                );
            } catch (PDOException) {
                // ignore si déjà présent
            }
        }

        $this->repairKnownBoissonTypes();
    }

    /**
     * Réattribue les types des boissons seed connues (souvent toutes sur id_type=1 après d'anciens bugs).
     */
    private function repairKnownBoissonTypes(): void
    {
        /** @var array<string, string> $byName */
        $byName = [
            'Jus de Fruit' => 'jus',
            'Cocktail de Fruits' => 'jus',
            'Smoothie Banane' => 'jus',
            'Milkshake' => 'soda',
            'Coca-Cola, Fanta, Sprite' => 'soda',
            'Eau Minérale' => 'eau',
            'Pinacolada' => 'alcool',
            'Mojito' => 'alcool',
            'Jack Daniels' => 'alcool',
            'Red Label' => 'alcool',
            'Heinekein' => 'alcool',
        ];

        try {
            $typeIds = [];
            foreach (['soda', 'eau', 'jus', 'alcool'] as $typeName) {
                $id = (int) ($this->pdo->query(
                    'SELECT id_type FROM type_boisson WHERE LOWER(TRIM(nom_type)) = ' . $this->pdo->quote($typeName) . ' LIMIT 1'
                )->fetchColumn() ?: 0);
                if ($id > 0) {
                    $typeIds[$typeName] = $id;
                }
            }

            $upd = $this->pdo->prepare('UPDATE boisson SET id_type = ? WHERE nom_boisson = ?');
            foreach ($byName as $drinkName => $typeName) {
                if (!isset($typeIds[$typeName])) {
                    continue;
                }
                $upd->execute([$typeIds[$typeName], $drinkName]);
            }
        } catch (PDOException) {
            // ignore
        }
    }

    /**
     * Fusionne les types doublons / synonymes vers les 4 types canoniques.
     * Ex. alcoolisé → alcool, naturelle → jus.
     */
    private function mergeBoissonTypeAliases(): void
    {
        /** @var array<string, string> $aliases alias normalisé => type canonique */
        $aliases = [
            'alcoolise' => 'alcool',
            'alcoolisé' => 'alcool',
            'alcoolisée' => 'alcool',
            'alcohol' => 'alcool',
            'naturelle' => 'jus',
            'naturel' => 'jus',
            'juice' => 'jus',
            'soft' => 'soda',
            'gazeux' => 'soda',
            'water' => 'eau',
        ];

        try {
            $rows = $this->pdo->query('SELECT id_type, nom_type FROM type_boisson')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return;
        }

        $canonicalIds = [];
        foreach ($rows as $row) {
            $name = mb_strtolower(trim((string) ($row['nom_type'] ?? '')), 'UTF-8');
            if (!in_array($name, ['soda', 'eau', 'jus', 'alcool'], true)) {
                continue;
            }
            $id = (int) $row['id_type'];
            // Garder l'id le plus bas en cas de doublons exacts.
            if (!isset($canonicalIds[$name]) || $id < $canonicalIds[$name]) {
                $canonicalIds[$name] = $id;
            }
        }

        foreach (['soda', 'eau', 'jus', 'alcool'] as $canonical) {
            if (!isset($canonicalIds[$canonical])) {
                $this->pdo->prepare('INSERT IGNORE INTO type_boisson (nom_type) VALUES (?)')->execute([$canonical]);
                $id = (int) ($this->pdo->query(
                    'SELECT id_type FROM type_boisson WHERE nom_type = ' . $this->pdo->quote($canonical) . ' LIMIT 1'
                )->fetchColumn() ?: 0);
                if ($id > 0) {
                    $canonicalIds[$canonical] = $id;
                }
            }
        }

        foreach ($rows as $row) {
            $id = (int) $row['id_type'];
            $raw = trim((string) ($row['nom_type'] ?? ''));
            $normalized = mb_strtolower($raw, 'UTF-8');
            // Retirer accents pour matcher "alcoolisé" / "alcoolise"
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            $ascii = is_string($ascii) ? strtolower(preg_replace('/[^a-z]/', '', $ascii) ?? '') : preg_replace('/[^a-z]/', '', $normalized) ?? '';

            $target = null;
            if (isset($aliases[$normalized])) {
                $target = $aliases[$normalized];
            } elseif (isset($aliases[$ascii])) {
                $target = $aliases[$ascii];
            } elseif (in_array($normalized, ['soda', 'eau', 'jus', 'alcool'], true)) {
                // Doublon exact du nom canonique (ex. deux lignes "alcool")
                $target = $normalized;
            }

            if ($target === null || !isset($canonicalIds[$target])) {
                continue;
            }

            $targetId = $canonicalIds[$target];
            if ($targetId === $id) {
                continue;
            }

            $this->reassignAndDeleteType($id, $targetId);
        }

        // Recharger et supprimer tout type non canonique restant (après réaffectation best-effort)
        try {
            $remaining = $this->pdo->query('SELECT id_type, nom_type FROM type_boisson')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($remaining as $row) {
                $name = mb_strtolower(trim((string) ($row['nom_type'] ?? '')), 'UTF-8');
                if (in_array($name, ['soda', 'eau', 'jus', 'alcool'], true)) {
                    // Garder uniquement le id canonique déjà choisi
                    $keepId = $canonicalIds[$name] ?? null;
                    if ($keepId !== null && $keepId !== (int) $row['id_type']) {
                        $this->reassignAndDeleteType((int) $row['id_type'], $keepId);
                    }
                    continue;
                }
                $id = (int) $row['id_type'];
                $fallback = $canonicalIds['soda'] ?? null;
                if ($fallback !== null) {
                    $this->reassignAndDeleteType($id, $fallback);
                } else {
                    try {
                        $this->pdo->prepare('DELETE FROM type_boisson WHERE id_type = ?')->execute([$id]);
                    } catch (PDOException) {
                        // ignore
                    }
                }
            }
        } catch (PDOException) {
            // ignore
        }
    }

    private function reassignAndDeleteType(int $fromId, int $toId): void
    {
        if ($fromId === $toId || $fromId <= 0 || $toId <= 0) {
            return;
        }

        try {
            $this->pdo->prepare('UPDATE boisson SET id_type = ? WHERE id_type = ?')->execute([$toId, $fromId]);
            $this->pdo->prepare('DELETE FROM type_boisson WHERE id_type = ?')->execute([$fromId]);
        } catch (PDOException) {
            // ignore FK issues
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
