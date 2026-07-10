<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Repository\FactureRepository;
use PDO;

final class StaffNotificationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly FactureRepository $factures,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db(), $app->factureRepository());
    }

    /** @return list<array<string, mixed>> */
    public function forRole(string $role): array
    {
        if ($role === 'cuisinier') {
            $stmt = $this->pdo->query("
                SELECT c.num_commande, c.statut, c.num_table, cl.nom_client, cl.prenom_client
                FROM commande c
                LEFT JOIN client cl ON c.id_client = cl.id_client
                WHERE c.statut = 'en_attente'
                ORDER BY c.date_commande ASC
                LIMIT 20
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($role === 'manager') {
            $stmt = $this->pdo->query("
                SELECT c.num_commande, c.statut, c.num_table, c.montant_total,
                       cl.nom_client, cl.prenom_client, cl.telephone_client,
                       COUNT(d.id_detail) AS nombre_items
                FROM commande c
                LEFT JOIN client cl ON c.id_client = cl.id_client
                LEFT JOIN contient d ON c.num_commande = d.num_commande
                WHERE c.statut = 'prete'
                GROUP BY c.num_commande, c.statut, c.num_table, c.montant_total,
                         cl.nom_client, cl.prenom_client, cl.telephone_client, c.date_commande
                ORDER BY c.date_commande ASC
                LIMIT 20
            ");
            $items = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $client = trim(($row['prenom_client'] ?? '') . ' ' . ($row['nom_client'] ?? ''));
                $items[] = [
                    'type' => 'prete',
                    'num_commande' => $row['num_commande'],
                    'num_table' => $row['num_table'],
                    'nom_client' => $client,
                    'telephone_client' => $row['telephone_client'] ?? '',
                    'nombre_items' => (int) ($row['nombre_items'] ?? 0),
                    'label' => 'Commande prête — table ' . ($row['num_table'] ?? '?'),
                    'href' => 'dashboard.php#cmd-' . (int) $row['num_commande'],
                ];
            }

            return $items;
        }

        if ($role === 'caissier') {
            $items = [];
            foreach ($this->factures->findPendingDemandes() as $d) {
                $items[] = [
                    'type' => 'demande',
                    'num_commande' => $d['num_commande'],
                    'label' => 'Demande paiement table ' . ($d['num_table'] ?? '?'),
                    'href' => 'paiement.php?voir_commande=' . (int) $d['num_commande'],
                ];
            }
            $stmt = $this->pdo->query("
                SELECT c.num_commande, c.num_table, cl.nom_client
                FROM commande c
                LEFT JOIN client cl ON c.id_client = cl.id_client
                WHERE c.statut = 'livree'
                  AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
                ORDER BY c.date_commande ASC
                LIMIT 8
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $items[] = [
                    'type' => 'encaissement',
                    'num_commande' => $c['num_commande'],
                    'label' => 'À encaisser — table ' . ($c['num_table'] ?? '?'),
                    'href' => 'paiement.php?voir_commande=' . (int) $c['num_commande'],
                ];
            }

            return $items;
        }

        return [];
    }
}
