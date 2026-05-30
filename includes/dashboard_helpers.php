<?php
/**
 * Helpers partagés pour les dashboards employés
 */

/**
 * Liens CSS/JS communs (chemins relatifs depuis admin/, cuisine/ ou caissier/)
 */
function dashboard_asset_links(string $pageTitle): void
{
    $title = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboards.css?v=4">
HTML;
}

function dashboard_scripts(): void
{
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="../assets/js/dashboard.js?v=2"></script>';
}

/**
 * @return list<array<string, mixed>>
 */
function dashboard_fetch_demandes_paiement(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'demande_paiement'");
        if ($stmt->fetchColumn() === false) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT 
                d.*,
                c.montant_total,
                c.date_commande,
                t.num_table,
                cl.nom_client,
                cl.prenom_client,
                cl.telephone_client
            FROM demande_paiement d
            JOIN commande c ON d.num_commande = c.num_commande
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE d.statut = 'en_attente'
            ORDER BY d.date_demande ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function dashboard_order_items_list(?string $detailsPlats): array
{
    if ($detailsPlats === null || $detailsPlats === '') {
        return [];
    }

    return array_values(array_filter(
        array_map('trim', explode(', ', $detailsPlats)),
        static fn(string $item): bool => $item !== ''
    ));
}
