<?php

/**
 * Ajustements BDD (tables fidélité, notifications) — idempotent.
 */
function schema_upgrade(PDO $pdo): void
{
    require_once __DIR__ . '/money.php';
    contient_ensure_schema($pdo);

    $pdo->exec("CREATE TABLE IF NOT EXISTS recompense_fidelite (
        id_recompense INT PRIMARY KEY AUTO_INCREMENT,
        libelle VARCHAR(120) NOT NULL,
        description VARCHAR(255) NULL,
        points_requis INT NOT NULL DEFAULT 0,
        type_recompense ENUM('pourcentage', 'montant_fixe', 'cadeau') NOT NULL DEFAULT 'pourcentage',
        valeur DECIMAL(10,2) NOT NULL DEFAULT 0,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS historique_points (
        id_historique INT PRIMARY KEY AUTO_INCREMENT,
        id_client INT NOT NULL,
        points INT NOT NULL,
        type_operation ENUM('gain', 'echange', 'ajustement', 'annulation') NOT NULL,
        description VARCHAR(255) NULL,
        num_commande INT NULL,
        id_recompense INT NULL,
        date_operation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notification (
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

    $commandeCols = array_column($pdo->query('SHOW COLUMNS FROM commande')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('remise_montant', $commandeCols, true)) {
        $pdo->exec('ALTER TABLE commande ADD COLUMN remise_montant DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER montant_total');
    }
    if (!in_array('id_recompense', $commandeCols, true)) {
        $pdo->exec('ALTER TABLE commande ADD COLUMN id_recompense INT NULL AFTER remise_montant');
    }
    if (!in_array('points_gagnes', $commandeCols, true)) {
        $pdo->exec('ALTER TABLE commande ADD COLUMN points_gagnes INT NOT NULL DEFAULT 0 AFTER id_recompense');
    }
    if (!in_array('instructions_speciales', $commandeCols, true)) {
        $after = in_array('mode_paiement_souhaite', $commandeCols, true) ? 'mode_paiement_souhaite' : 'montant_total';
        $pdo->exec("ALTER TABLE commande ADD COLUMN instructions_speciales TEXT NULL AFTER {$after}");
    }

    $platCols = array_column($pdo->query('SHOW COLUMNS FROM plat')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('prix_unitaire', $platCols, true)) {
        if (in_array('categorie', $platCols, true)) {
            $pdo->exec("ALTER TABLE plat ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER nom_plat");
        } else {
            $pdo->exec("ALTER TABLE plat ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0");
        }
        $platCols[] = 'prix_unitaire';
    }
    if (!in_array('image_url', $platCols, true)) {
        $pdo->exec("ALTER TABLE plat ADD COLUMN image_url VARCHAR(255) NULL AFTER categorie");
    }
    if (!in_array('quantite_plat', $platCols, true)) {
        $pdo->exec("ALTER TABLE plat ADD COLUMN quantite_plat INT NOT NULL DEFAULT 0 AFTER categorie");
    }
    $boissonCols = array_column($pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('id_type', $boissonCols, true)) {
        if (in_array('type_boisson', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN id_type INT NULL AFTER type_boisson");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN id_type INT NULL");
        }
        $boissonCols[] = 'id_type';
    }
    if (!in_array('dosage', $boissonCols, true)) {
        if (in_array('type_boisson', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN dosage VARCHAR(100) NULL AFTER type_boisson");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN dosage VARCHAR(100) NULL");
        }
        $boissonCols[] = 'dosage';
    }
    if (!in_array('quantite_boisson', $boissonCols, true)) {
        if (in_array('dosage', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN quantite_boisson INT DEFAULT 0 AFTER dosage");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN quantite_boisson INT DEFAULT 0");
        }
        $boissonCols[] = 'quantite_boisson';
    }
    if (!in_array('options_fruits', $boissonCols, true)) {
        if (in_array('quantite_boisson', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) NULL AFTER quantite_boisson");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) NULL");
        }
        $boissonCols[] = 'options_fruits';
    }
    if (!in_array('prix_unitaire', $boissonCols, true)) {
        if (in_array('quantite_boisson', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite_boisson");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0");
        }
        $boissonCols[] = 'prix_unitaire';
    }
    if (!in_array('image_url', $boissonCols, true)) {
        if (in_array('options_fruits', $boissonCols, true)) {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL AFTER options_fruits");
        } else {
            $pdo->exec("ALTER TABLE boisson ADD COLUMN image_url VARCHAR(255) NULL");
        }
    }

    $typeBoissonExists = count($pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)) > 0;
    if ($typeBoissonExists) {
        $typeBoissonCols = array_column($pdo->query('SHOW COLUMNS FROM type_boisson')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (in_array('id_boisson', $typeBoissonCols, true) && !in_array('id_type', $typeBoissonCols, true)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS type_boisson_new (
                id_type INT PRIMARY KEY AUTO_INCREMENT,
                nom_type VARCHAR(100) NOT NULL UNIQUE
            )");
            $pdo->exec("INSERT IGNORE INTO type_boisson_new (nom_type) SELECT DISTINCT nom_type FROM type_boisson");
            $pdo->exec("DROP TABLE type_boisson");
            $pdo->exec("RENAME TABLE type_boisson_new TO type_boisson");
            $typeBoissonExists = true;
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS type_boisson (
        id_type INT PRIMARY KEY AUTO_INCREMENT,
        nom_type VARCHAR(100) NOT NULL UNIQUE
    )");
    $pdo->exec("INSERT IGNORE INTO type_boisson (id_type, nom_type) VALUES (1, 'soda')");
    $pdo->exec("UPDATE boisson SET id_type = 1");
    if (in_array('type_boisson', $boissonCols, true)) {
        try {
            $pdo->exec('ALTER TABLE boisson DROP COLUMN type_boisson');
        } catch (PDOException $e) {
            // ignore if the column cannot be dropped yet
        }
    }

    try {
        $pdo->exec("ALTER TABLE boisson ADD CONSTRAINT fk_boisson_type_boisson FOREIGN KEY (id_type) REFERENCES type_boisson(id_type) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // ignore if the foreign key already exists or cannot be added
    }

    try {
        require_once __DIR__ . '/menu_image_index.php';
        $tables = ['plat' => 'id_plat', 'boisson' => 'id_boisson'];
        foreach ($tables as $tableName => $idCol) {
            if ($pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName))->fetchColumn() === false) {
                continue;
            }
            $cols = array_column($pdo->query('SHOW COLUMNS FROM ' . $tableName)->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (!in_array('image_url', $cols, true)) {
                continue;
            }
            if ($tableName === 'plat' && in_array('categorie', $cols, true)) {
                $pdo->exec("UPDATE plat SET categorie = 'Combo' WHERE categorie = 'Kombo'");
            }
            $pdo->exec(
                "UPDATE {$tableName} SET image_url = REPLACE(REPLACE(image_url, 'images/kombo/', 'images/combo/'), 'images/Kombo/', 'images/combo/')
                 WHERE image_url LIKE '%images/kombo/%' OR image_url LIKE '%images/Kombo/%'"
            );
            $stmt = $pdo->query("SELECT {$idCol}, image_url FROM {$tableName} WHERE image_url IS NOT NULL AND image_url <> ''");
            $update = $pdo->prepare("UPDATE {$tableName} SET image_url = ? WHERE {$idCol} = ?");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $normalized = normalize_menu_image_path((string) $row['image_url']);
                if ($normalized !== null && $normalized !== $row['image_url']) {
                    $update->execute([$normalized, (int) $row[$idCol]]);
                }
            }
        }
    } catch (PDOException $e) {
        // ignore
    }

    $clientCols = array_column($pdo->query('SHOW COLUMNS FROM client')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('niveau_fidelite', $clientCols, true)) {
        $pdo->exec("ALTER TABLE client ADD COLUMN niveau_fidelite ENUM('bronze','argent','or') NOT NULL DEFAULT 'bronze' AFTER points");
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM recompense_fidelite')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO recompense_fidelite (libelle, description, points_requis, type_recompense, valeur, actif) VALUES (?, ?, ?, ?, ?, 1)');
        $defaults = [
            ['5 % de réduction', 'Sur la commande en cours', 25, 'pourcentage', 5],
            ['10 % de réduction', 'Réservé aux clients fidèles', 50, 'pourcentage', 10],
            ['5 600 FC offerts', 'Remise fixe immédiate', 30, 'montant_fixe', 5600],
            ['Dessert offert', 'Cadeau maison', 80, 'cadeau', 0],
        ];
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    }

    // Seed default menu items if missing, using the static menu definition from the client.
    $baseImageDir = dirname(__DIR__) . '/assets/images';
    $imageFiles = [];
    if (is_dir($baseImageDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseImageDir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $imageFiles[strtolower($file->getFilename())] = str_replace('\\', '/', substr($file->getPathname(), strlen(dirname(__DIR__)) + 1));
            }
        }
    }

    $staticItems = [
        ['Pizza Margherita', 'Plats principaux', 24, 'Pizza.jpg', 'plat'],
        ['Tacos Maison', 'Plats principaux', 27, 'Tacos.jpg', 'plat'],
        ['Poulet Mayo', 'Plats principaux', 22, 'Poulet mayo.jpg', 'plat'],
        ['Spaghetti Bolognaise', 'Plats principaux', 19, 'spaghetti bolognaise.jpg', 'plat'],
        ['Fried Rice', 'Plats principaux', 18, 'Fried rice.jpg', 'plat'],
        ['Crevettes Sautées', 'Plats principaux', 34, 'Crevetes.jpg', 'plat'],
        ['Poisson Grillé', 'Plats principaux', 36, 'poisson ambassade.jpg', 'plat'],
        ['Poisson Fumé', 'Plats principaux', 28, 'Poisson fumé.jpg', 'plat'],
        ['Ntaba', 'Plats principaux', 30, 'Ntaba.jpg', 'plat'],
        ['Poisson Salé', 'Plats principaux', 26, 'Poisson salé.jpg', 'plat'],
        ['Poulet Rôti', 'Plats principaux', 23, 'poulet.jpg', 'plat'],
        ['Macaroni Saucisse', 'Plats principaux', 20, 'pates aux saucisses.png', 'plat'],
        ['Saucisses Grillées', 'Plats principaux', 17, 'Saucisses.jpg', 'plat'],
        ['Combo Burger Poulet', 'Plats principaux', 29, 'combo burger frites poulet.jpg', 'plat'],
        ['Fufu et Sauce', 'Plats principaux', 16, 'Fufu.jpg', 'plat'],
        ['Burger Maison', 'Plats principaux', 21, 'KFC.jpg', 'plat'],
        ['Saucisses & Frites', 'Plats principaux', 26, 'Saucisses frites.jpg', 'plat'],
        ['Makoso', 'Plats principaux', 25, 'makoso.jpg', 'plat'],
        ['Samoussa', 'Apéritifs', 6, 'Samoussa.jpg', 'plat'],
        ['Croquettes au fromage', 'Apéritifs', 5, 'croque monsieur.png', 'plat'],
        ['Croquettes aux pommes de terre', 'Apéritifs', 5, 'croquettes aux pommes de terre.png', 'plat'],
        ['4 Petits pains', 'Apéritifs', 6, 'petits pains.png', 'plat'],
        ['3 Croissants au beurre', 'Apéritifs', 6, 'pancakes.png', 'plat'],
        ['Salade Verte', 'Entrées', 8, 'salade aux légumes.png', 'plat'],
        ['Soupe du Jour', 'Entrées', 9, 'soupes aux légumes.png', 'plat'],
        ['Salade Avocat', 'Entrées', 8, 'salade avocat.png', 'plat'],
        ['Carpaccio de Boeuf', 'Entrées', 12, 'bouillon à la viande de boeuf.png', 'plat'],
        ['Combo 2 Burger + frites + coca', 'Combo', 55, 'combo 2burgers frites et coca.png', 'plat'],
        ['Combo Burger', 'Combo', 28, 'combo burger frites poulet.jpg', 'plat'],
        ['Combo Sandwich', 'Combo', 30, 'combo sandwich frites.png', 'plat'],
        ['Combo Croque monsieur', 'Combo', 32, 'combo 3croques monsieur frites et mojito.png', 'plat'],
        ['Gâteau au Chocolat', 'Desserts', 7, 'Gateau au chocolat.jpg', 'plat'],
        ['Glace à la Banane', 'Desserts', 6, 'glace a la banane.jpg', 'plat'],
        ['Churros', 'Desserts', 6, 'spring au chocolat.png', 'plat'],
        ['Salade de fruit', 'Desserts', 7, 'salade de fruit.png', 'plat'],
        ['Crepes au chocolat', 'Desserts', 7, 'crepes au chocolat.jpg', 'plat'],
        ['Tarte aux pommes', 'Desserts', 7, 'tarte aux pommes.jpg', 'plat'],
        ['Frites', 'Accompagnements', 4, 'Frites.jpg', 'plat'],
        ['Fufu', 'Accompagnements', 4, 'Fufu.jpg', 'plat'],
        ['Riz Blanc', 'Accompagnements', 3, 'Riz blanc.jpg', 'plat'],
        ['Pommes de Terre', 'Accompagnements', 4, 'Pomme de terre.jpg', 'plat'],
        ['Chikwangue', 'Accompagnements', 4, 'Chikwangue.jpg', 'plat'],
        ['Bananes Plantain', 'Accompagnements', 5, 'Bananes.jpg', 'plat'],
        ['Jus de Fruit', 'Boissons', 4, 'Jus de fruit.jpg', 'boisson'],
        ['Milkshake', 'Boissons', 5, 'Milkshakes.jpg', 'boisson'],
        ['Cocktail de Fruits', 'Boissons', 5, 'Coktail de fruit.jpg', 'boisson'],
        ['Smoothie Banane', 'Boissons', 5, 'glace a la banane.jpg', 'boisson'],
        ['Coca-Cola, Fanta, Sprite', 'Boissons', 3, 'boissons coca cola.png', 'boisson'],
        ['Eau Minérale', 'Boissons', 2, null, 'boisson'],
        ['Pinacolada', 'Boissons', 3, 'pinnacolada.png', 'boisson'],
        ['Mojito', 'Boissons', 3, 'mojito.png', 'boisson'],
        ['Jack Daniels', 'Boissons', 4, 'whisky jack daniel.jpg', 'boisson'],
        ['Red Label', 'Boissons', 5, 'whisky red label.jpg', 'boisson'],
        ['Heinekein', 'Boissons', 5, 'bierre heinekein.jpg', 'boisson'],
    ];

    $platStmt = $pdo->prepare('SELECT categorie, image_url FROM plat WHERE nom_plat = ?');
    $insertPlat = $pdo->prepare('INSERT INTO plat (nom_plat, prix_unitaire, categorie, quantite_plat, image_url) VALUES (?, ?, ?, ?, ?)');
    $updatePlat = $pdo->prepare('UPDATE plat SET categorie = ?, image_url = COALESCE(NULLIF(?, \'\'), image_url) WHERE nom_plat = ?');
    $boissonStmt = $pdo->prepare('SELECT image_url FROM boisson WHERE nom_boisson = ?');
    $insertBoisson = $pdo->prepare('INSERT INTO boisson (nom_boisson, id_type, dosage, quantite_boisson, prix_unitaire, options_fruits, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $updateBoisson = $pdo->prepare('UPDATE boisson SET image_url = ? WHERE nom_boisson = ?');

    foreach ($staticItems as [$name, $category, $price, $filename, $type]) {
        $image = $filename ? ($imageFiles[strtolower($filename)] ?? null) : null;
        if ($type === 'plat') {
            $platStmt->execute([$name]);
            $existing = $platStmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $insertPlat->execute([$name, $price, $category, 0, $image]);
            } else {
                $currentCategory = trim((string) ($existing['categorie'] ?? ''));
                $currentImage = trim((string) ($existing['image_url'] ?? ''));
                if ($currentCategory !== $category || ($currentImage === '' && $image !== null)) {
                    $updatePlat->execute([$category, $image, $name]);
                }
            }
        } else {
            $boissonStmt->execute([$name]);
            $existing = $boissonStmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $insertBoisson->execute([$name, 1, '', 0, $price, '', $image]);
            } else {
                $currentImage = trim((string) ($existing['image_url'] ?? ''));
                if ($currentImage === '' && $image !== null) {
                    $updateBoisson->execute([$image, $name]);
                }
            }
        }
    }

    $pdo->exec('UPDATE boisson SET id_type = 1');

    require_once __DIR__ . '/employe_passwords.php';
    employe_upgrade_passwords($pdo);
}
