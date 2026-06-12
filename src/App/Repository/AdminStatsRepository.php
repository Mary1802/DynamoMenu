<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AdminStatsRepository extends BaseRepository
{
    /** @return array{nb:int, ca:float, ca_especes:float, ca_mobile:float, ca_carte:float} */
    public function salesTotals(string $scope, string $value): array
    {
        $empty = ['nb' => 0, 'ca' => 0.0, 'ca_especes' => 0.0, 'ca_mobile' => 0.0, 'ca_carte' => 0.0];

        if ($scope === 'day') {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS nb,
                       COALESCE(SUM(total_paye), 0) AS ca,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN total_paye ELSE 0 END), 0) AS ca_especes,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN total_paye ELSE 0 END), 0) AS ca_mobile,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN total_paye ELSE 0 END), 0) AS ca_carte
                FROM facture
                WHERE DATE(date_facture) = ?
            ");
            $stmt->execute([$value]);
        } elseif ($scope === 'month') {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS nb,
                       COALESCE(SUM(total_paye), 0) AS ca,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN total_paye ELSE 0 END), 0) AS ca_especes,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN total_paye ELSE 0 END), 0) AS ca_mobile,
                       COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN total_paye ELSE 0 END), 0) AS ca_carte
                FROM facture
                WHERE DATE_FORMAT(date_facture, '%Y-%m') = ?
            ");
            $stmt->execute([$value]);
        } else {
            return $empty;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'nb' => (int) ($row['nb'] ?? 0),
            'ca' => (float) ($row['ca'] ?? 0),
            'ca_especes' => (float) ($row['ca_especes'] ?? 0),
            'ca_mobile' => (float) ($row['ca_mobile'] ?? 0),
            'ca_carte' => (float) ($row['ca_carte'] ?? 0),
        ];
    }

    /** @return array<string, int|float> */
    public function dashboardStats(float $caJour, float $caMois): array
    {
        return [
            'total_orders' => (int) $this->pdo->query('SELECT COUNT(*) FROM commande')->fetchColumn(),
            'total_revenue' => $caMois,
            'revenue_day' => $caJour,
            'revenue_month' => $caMois,
            'active_clients' => (int) $this->pdo->query('SELECT COUNT(*) FROM client')->fetchColumn(),
            'pending_orders' => (int) $this->pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_attente'")->fetchColumn(),
            'preparing_orders' => (int) $this->pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_preparation'")->fetchColumn(),
            'ready_orders' => (int) $this->pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'prete'")->fetchColumn(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function recentOrders(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nom_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            ORDER BY c.date_commande DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function topPlats(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.nom_plat, COUNT(d.id_detail) AS ventes, SUM(d.sous_total) AS revenu
            FROM contient d
            JOIN plat p ON d.id_plat = p.id_plat
            GROUP BY p.id_plat, p.nom_plat
            ORDER BY ventes DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
