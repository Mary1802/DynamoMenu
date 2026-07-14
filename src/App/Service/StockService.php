<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use PDO;
use PDOException;
use RuntimeException;

final class StockService
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db());
    }

    public function availableBoisson(int $idBoisson): int
    {
        if ($idBoisson <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT quantite_boisson FROM boisson WHERE id_boisson = ?');
        $stmt->execute([$idBoisson]);
        $val = $stmt->fetchColumn();

        return $val === false ? 0 : max(0, (int) $val);
    }

    /**
     * Agrège les quantités demandées par article (panier ou lignes commande).
     * Les plats n'ont plus de stock : seuls les boissons sont comptés pour le stock.
     *
     * @param list<array<string, mixed>> $cartItems
     * @return array{boissons: array<int, int>, unresolved: list<string>}
     */
    public function aggregateCartNeeds(array $cartItems): array
    {
        $boissons = [];
        $unresolved = [];

        foreach ($cartItems as $item) {
            $qty = max(0, (int) ($item['quantite'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            $idPlat = 0;
            $idBoisson = 0;

            if ($type === 'plat') {
                $idPlat = (int) ($item['id'] ?? $item['id_plat'] ?? 0);
            } elseif ($type === 'boisson') {
                $idBoisson = (int) ($item['id'] ?? $item['id_boisson'] ?? 0);
            } else {
                $idPlat = (int) ($item['id_plat'] ?? 0);
                $idBoisson = (int) ($item['id_boisson'] ?? 0);
                if ($idPlat <= 0 && $idBoisson <= 0) {
                    [$idPlat, $idBoisson] = $this->resolveMenuItemIds(
                        (string) ($item['nom'] ?? ''),
                        (string) ($item['category'] ?? '')
                    );
                }
            }

            if ($idPlat > 0) {
                continue;
            }

            if ($idBoisson > 0) {
                $boissons[$idBoisson] = ($boissons[$idBoisson] ?? 0) + $qty;
            } else {
                $name = trim((string) ($item['nom'] ?? ''));
                if ($name !== '') {
                    $unresolved[] = $name;
                }
            }
        }

        return [
            'boissons' => $boissons,
            'unresolved' => $unresolved,
        ];
    }

    /**
     * @param list<array<string, mixed>> $cartItems
     * @return string|null message d'erreur ou null si OK
     */
    public function validateCartAvailability(array $cartItems): ?string
    {
        $needs = $this->aggregateCartNeeds($cartItems);

        if ($needs['unresolved'] !== []) {
            return 'Article introuvable en stock : ' . $needs['unresolved'][0] . '.';
        }

        foreach ($needs['boissons'] as $id => $qty) {
            $available = $this->availableBoisson($id);
            if ($available < $qty) {
                $nom = $this->boissonName($id);

                return $available <= 0
                    ? "« {$nom} » est en rupture de stock."
                    : "Stock insuffisant pour « {$nom} » (disponible : {$available}).";
            }
        }

        return null;
    }

    /**
     * Décrémente le stock boissons pour le panier. À appeler dans une transaction ouverte.
     *
     * @param list<array<string, mixed>> $cartItems
     */
    public function decrementForCart(array $cartItems): void
    {
        $error = $this->validateCartAvailability($cartItems);
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $needs = $this->aggregateCartNeeds($cartItems);

        foreach ($needs['boissons'] as $id => $qty) {
            $stmt = $this->pdo->prepare(
                'UPDATE boisson SET quantite_boisson = quantite_boisson - ? WHERE id_boisson = ? AND quantite_boisson >= ?'
            );
            $stmt->execute([$qty, $id, $qty]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Stock insuffisant pour « ' . $this->boissonName($id) . ' ».');
            }
        }
    }

    /** Restaure le stock des lignes boisson d'une commande (ex. annulation). */
    public function restoreForOrder(int $numCommande): void
    {
        if ($numCommande <= 0) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id_boisson, quantite FROM contient WHERE num_commande = ? AND id_boisson IS NOT NULL'
            );
            $stmt->execute([$numCommande]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return;
        }

        foreach ($rows as $row) {
            $qty = max(0, (int) ($row['quantite'] ?? 0));
            $idBoisson = (int) ($row['id_boisson'] ?? 0);
            if ($qty <= 0 || $idBoisson <= 0) {
                continue;
            }

            $this->pdo->prepare(
                'UPDATE boisson SET quantite_boisson = quantite_boisson + ? WHERE id_boisson = ?'
            )->execute([$qty, $idBoisson]);
        }
    }

    /**
     * Quantité déjà présente dans le panier pour une boisson (hors index exclus).
     *
     * @param list<array<string, mixed>> $panier
     */
    public function quantityAlreadyInCart(
        array $panier,
        ?int $idBoisson,
        ?int $excludeIndex = null,
    ): int {
        if ($idBoisson === null || $idBoisson <= 0) {
            return 0;
        }

        $total = 0;
        foreach ($panier as $i => $item) {
            if ($excludeIndex !== null && (int) $i === $excludeIndex) {
                continue;
            }

            $itemBoisson = (int) ($item['id_boisson'] ?? ($item['type'] === 'boisson' ? ($item['id'] ?? 0) : 0));
            if ($itemBoisson === $idBoisson) {
                $total += (int) ($item['quantite'] ?? 0);
            }
        }

        return $total;
    }

    /** @return array{0: int, 1: int} */
    private function resolveMenuItemIds(string $name, string $category): array
    {
        $name = trim($name);
        if ($name === '') {
            return [0, 0];
        }

        if (mb_strtolower(trim($category)) === 'boissons') {
            $stmt = $this->pdo->prepare('SELECT id_boisson FROM boisson WHERE nom_boisson = ? LIMIT 1');
            $stmt->execute([$name]);

            return [0, (int) $stmt->fetchColumn()];
        }

        $stmt = $this->pdo->prepare('SELECT id_plat FROM plat WHERE nom_plat = ? LIMIT 1');
        $stmt->execute([$name]);

        return [(int) $stmt->fetchColumn(), 0];
    }

    private function boissonName(int $id): string
    {
        $stmt = $this->pdo->prepare('SELECT nom_boisson FROM boisson WHERE id_boisson = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();

        return is_string($name) && $name !== '' ? $name : ('boisson #' . $id);
    }
}
