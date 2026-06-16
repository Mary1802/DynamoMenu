<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Repository\ClientRepository;
use PDO;
use Throwable;

final class OrderCreationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ClientRepository $clients,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            $app->db(),
            $app->clientRepository()
        );
    }

    /**
     * @param list<array<string, mixed>> $cartItems
     * @param array<string, mixed> $post
     * @return array{success: true, num_commande: int, total_ttc: float, remise: float, num_table: string|int}|array{success: false, error: string}
     */
    public function createFromCheckout(
        array $post,
        array $cartItems,
        string $numTable,
        float $totalPanier,
        float $tvaRate = 0.16
    ): array {
        $nom = trim((string) ($post['nom'] ?? ''));
        $prenom = trim((string) ($post['prenom'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));
        $telephone = trim((string) ($post['telephone'] ?? ''));
        $modePaiement = (string) ($post['mode_paiement_souhaite'] ?? '');
        $instructions = mb_substr(trim((string) ($post['instructions'] ?? '')), 0, 1000);

        if ($nom === '' || $prenom === '' || $email === '' || $telephone === '' || $numTable === '') {
            return ['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires (nom, prénom, email, téléphone).'];
        }

        if (!in_array($modePaiement, ['especes', 'mobile_money'], true)) {
            return ['success' => false, 'error' => 'Veuillez choisir un mode de paiement.'];
        }

        if ($cartItems === []) {
            return ['success' => false, 'error' => 'Votre panier est vide.'];
        }

        $this->clients->ensureSchema();
        Application::getInstance()->tableRepository()->ensureSchema();

        $tvaAmount = $totalPanier * $tvaRate;
        $totalTtc = round($totalPanier + $tvaAmount, 2);

        $this->pdo->beginTransaction();

        try {
            $idClient = $this->clients->upsert($nom, $prenom, $email, $telephone);

            $stmt = $this->pdo->prepare("
                INSERT INTO commande (id_client, num_table, montant_total, mode_paiement_souhaite, instructions_speciales, statut)
                VALUES (?, ?, ?, ?, ?, 'en_attente')
            ");
            $stmt->execute([
                $idClient,
                $numTable,
                $totalTtc,
                $modePaiement,
                $instructions !== '' ? $instructions : null,
            ]);
            $numCommande = (int) $this->pdo->lastInsertId();

            $this->insertCartLines($numCommande, $cartItems);

            $this->pdo->commit();

            return [
                'success' => true,
                'num_commande' => $numCommande,
                'total_ttc' => $totalTtc,
                'remise' => 0.0,
                'num_table' => $numTable,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'error' => 'Erreur lors de la création de la commande: ' . $e->getMessage()];
        }
    }

    /** @param list<array<string, mixed>> $cartItems */
    private function insertCartLines(int $numCommande, array $cartItems): void
    {
        foreach ($cartItems as $item) {
            $type = (string) ($item['type'] ?? '');

            if ($type === 'plat') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO contient (num_commande, id_plat, quantite, prix, sous_total, sauces)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $numCommande,
                    $item['id'],
                    $item['quantite'],
                    $item['prix'],
                    $item['sous_total'],
                    $item['sauces'] ?? '',
                ]);
            } elseif ($type === 'boisson') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO contient (num_commande, id_boisson, quantite, prix, sous_total, personnalisation_boisson)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $numCommande,
                    $item['id'],
                    $item['quantite'],
                    $item['prix'],
                    $item['sous_total'],
                    $item['personnalisation'] ?? '',
                ]);
            } elseif ($type === 'menu_item') {
                $label = (string) $item['nom'];
                if (!empty($item['personnalisation'])) {
                    $label .= ' — ' . $item['personnalisation'];
                }
                $stmt = $this->pdo->prepare("
                    INSERT INTO contient (num_commande, id_plat, id_boisson, quantite, prix, sous_total, personnalisation_boisson)
                    VALUES (?, NULL, NULL, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $numCommande,
                    $item['quantite'],
                    $item['prix'],
                    $item['sous_total'],
                    $label,
                ]);
            }
        }
    }
}
