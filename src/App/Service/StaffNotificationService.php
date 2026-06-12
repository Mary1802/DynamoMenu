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
