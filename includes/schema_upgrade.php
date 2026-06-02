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
}
