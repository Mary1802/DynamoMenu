-- Script de mise à jour de la base de données DynamoMenu
-- Exécutez ce script dans phpMyAdmin ou votre outil MySQL préféré

USE dynamomenu;

-- 1. Ajouter les colonnes manquantes à la table client
ALTER TABLE client 
ADD COLUMN prenom_client VARCHAR(100) AFTER nom_client,
ADD COLUMN email_client VARCHAR(100) AFTER prenom_client;

-- 2. Ajouter les colonnes manquantes à la table boisson
ALTER TABLE boisson 
ADD COLUMN type_boisson ENUM('soda', 'eau', 'jus', 'alcool') DEFAULT 'soda' AFTER nom_boisson,
ADD COLUMN options_fruits VARCHAR(255) DEFAULT '' AFTER quantite_boisson;

-- 3. Ajouter les colonnes manquantes à la table contient
ALTER TABLE contient 
ADD COLUMN sauces VARCHAR(255) DEFAULT '' AFTER sous_total,
ADD COLUMN personnalisation_boisson VARCHAR(255) DEFAULT '' AFTER sauces;

-- 4. Ajouter la colonne mode_paiement à la table facture
ALTER TABLE facture 
ADD COLUMN mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL AFTER total_paye;

-- 5. Créer la table demande_paiement
CREATE TABLE IF NOT EXISTS demande_paiement (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    num_commande INT NOT NULL,
    mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    statut ENUM('en_attente', 'traitee', 'annulee') DEFAULT 'en_attente',
    date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_traitement TIMESTAMP NULL,
    FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE
);

-- 6. Mettre à jour les types de boissons existantes
UPDATE boisson SET type_boisson = 'soda' WHERE nom_boisson LIKE '%Soda%' OR nom_boisson LIKE '%Coca%';
UPDATE boisson SET type_boisson = 'eau' WHERE nom_boisson LIKE '%Eau%';
UPDATE boisson SET type_boisson = 'jus' WHERE nom_boisson LIKE '%Jus%';

-- 7. Mettre à jour les options de fruits pour les jus
UPDATE boisson SET options_fruits = 'orange,pomme,ananas,mangue' WHERE nom_boisson = 'Jus de Fruit';
UPDATE boisson SET options_fruits = 'orange' WHERE nom_boisson = 'Jus d\'Orange';
UPDATE boisson SET options_fruits = 'pomme' WHERE nom_boisson = 'Jus de Pomme';

-- 8. Ajouter de nouvelles boissons
INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES
('Coca-Cola', 'soda', '33cl', 50, ''),
('Jus d\'Orange', 'jus', '25cl', 30, 'orange'),
('Jus de Pomme', 'jus', '25cl', 30, 'pomme');

-- 9. Mettre à jour les informations des clients existants
UPDATE client SET prenom_client = 'Jean', email_client = 'jean.dupont@email.com' WHERE nom_client = 'Dupont';
UPDATE client SET prenom_client = 'Sophie', email_client = 'sophie.martin@email.com' WHERE nom_client = 'Martin';
UPDATE client SET prenom_client = 'Luc', email_client = 'luc.bernard@email.com' WHERE nom_client = 'Bernard';

-- 10. Mettre à jour les factures existantes avec un mode de paiement par défaut
UPDATE facture SET mode_paiement = 'carte' WHERE mode_paiement IS NULL OR mode_paiement = '';

-- Message de confirmation
SELECT '✅ Base de données mise à jour avec succès !' as message;