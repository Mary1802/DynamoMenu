<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Security\Csrf;
use PDO;

final class CartService
{
    public function __construct(
        private readonly ClientSessionService $session,
        private readonly Csrf $csrf,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->clientSession(), $app->csrf());
    }

    public static function makeKey(string $type, string $name, string $category = '', string $personalization = ''): string
    {
        $payload = mb_strtolower(trim($type . '|' . $name . '|' . $category . '|' . $personalization));

        return $type . ':' . md5($payload);
    }

    /** @param list<array<string, mixed>> $panier */
    public static function indexIn(array $panier, string $cartKey): ?int
    {
        foreach ($panier as $i => $item) {
            if (($item['cart_key'] ?? '') === $cartKey) {
                return (int) $i;
            }
        }

        return null;
    }

    /** @param list<array<string, mixed>> $panier @return list<string> */
    public static function keysFrom(array $panier): array
    {
        $keys = [];
        foreach ($panier as $item) {
            if (!empty($item['cart_key'])) {
                $keys[] = (string) $item['cart_key'];
            }
        }

        return $keys;
    }

    public function ensureCart(): void
    {
        $this->session->start();

        if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }
    }

    /** @return array{count: int, keys: list<string>} */
    public function countSummary(): array
    {
        $this->ensureCart();

        $count = 0;
        $keys = [];

        foreach ($_SESSION['panier'] as $item) {
            $count += (int) ($item['quantite'] ?? 1);
            if (!empty($item['cart_key'])) {
                $keys[] = (string) $item['cart_key'];
            }
        }

        return [
            'count' => $count,
            'keys' => array_values(array_unique($keys)),
        ];
    }

    /** @return list<string> */
    public function listKeys(): array
    {
        $this->ensureCart();

        return self::keysFrom($_SESSION['panier']);
    }

    public function findIndex(string $cartKey): ?int
    {
        $this->ensureCart();

        return self::indexIn($_SESSION['panier'], $cartKey);
    }

    public function isDuplicatePlat(string $type, string $personalization): bool
    {
        return in_array($type, ['menu_item', 'plat'], true) && trim($personalization) === '';
    }

    public function drinkKind(string $name): ?string
    {
        $n = mb_strtolower(trim($name));
        if ($n === 'coca-cola, fanta, sprite' || (str_contains($n, 'coca') && str_contains($n, 'fanta') && str_contains($n, 'sprite'))) {
            return 'soda';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public function handleAjaxAdd(array $post): array
    {
        $this->ensureCart();

        if (!$this->csrf->verify()) {
            return ['success' => false, 'message' => 'Session expirée. Rechargez la page.'];
        }

        $type = (string) ($post['type'] ?? 'menu_item');
        $name = trim((string) ($post['name'] ?? ''));
        $price = (float) ($post['price'] ?? 0);
        if ($price > 0 && $price < 500) {
            $price = Application::getInstance()->moneyFormatter()->fromMenuUnit($price);
        }
        $quantite = max(1, (int) ($post['quantite'] ?? 1));
        $img = (string) ($post['img'] ?? '');
        $category = (string) ($post['category'] ?? '');
        $personnalisation = trim((string) ($post['personnalisation'] ?? ''));
        $idPlat = (int) ($post['id_plat'] ?? 0);
        $idBoisson = (int) ($post['id_boisson'] ?? 0);

        if ($name === '' || $price <= 0) {
            return ['success' => false, 'message' => 'Données invalides'];
        }

        if ($idBoisson > 0) {
            $stock = StockService::fromApp();
            $already = $stock->quantityAlreadyInCart($_SESSION['panier'], $idBoisson);
            $needed = $already + $quantite;
            $available = $stock->availableBoisson($idBoisson);
            if ($available <= 0) {
                return ['success' => false, 'message' => '« ' . $name . ' » est en rupture de stock.'];
            }
            if ($needed > $available) {
                return [
                    'success' => false,
                    'message' => "Stock insuffisant pour « {$name} » (disponible : {$available}).",
                ];
            }
        }

        $cartKey = self::makeKey($type, $name, $category, $personnalisation);

        if ($this->isDuplicatePlat($type, $personnalisation) && $this->findIndex($cartKey) !== null) {
            return [
                'success' => false,
                'duplicate' => true,
                'message' => 'Cet article est déjà dans votre panier. Modifiez la quantité depuis le panier.',
            ];
        }

        $_SESSION['panier'][] = [
            'type' => $type,
            'nom' => $name,
            'prix' => $price,
            'quantite' => $quantite,
            'sous_total' => round($price * $quantite, 2),
            'img' => $img,
            'category' => $category,
            'personnalisation' => $personnalisation,
            'cart_key' => $cartKey,
            'id_plat' => $idPlat > 0 ? $idPlat : null,
            'id_boisson' => $idBoisson > 0 ? $idBoisson : null,
        ];

        $summary = $this->countSummary();

        return [
            'success' => true,
            'count' => $summary['count'],
            'cart_key' => $cartKey,
            'keys' => $summary['keys'],
        ];
    }

    /** @param array<string, mixed> $post */
    public function addFromForm(PDO $pdo, array $post): void
    {
        $this->ensureCart();

        $type = (string) ($post['type'] ?? '');
        $id = (int) ($post['id'] ?? 0);
        $quantite = (int) ($post['quantite'] ?? 1);
        $stock = StockService::fromApp();

        if ($type === 'plat' && $id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM plat WHERE id_plat = ?');
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $sauces = isset($post['sauces']) && is_array($post['sauces'])
                    ? implode(',', $post['sauces'])
                    : '';
                $_SESSION['panier'][] = [
                    'type' => 'plat',
                    'id' => $id,
                    'id_plat' => $id,
                    'nom' => $item['nom_plat'],
                    'prix' => $item['prix_unitaire'],
                    'quantite' => $quantite,
                    'sous_total' => $item['prix_unitaire'] * $quantite,
                    'sauces' => $sauces,
                ];
            }
        } elseif ($type === 'boisson' && $id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM boisson WHERE id_boisson = ?');
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $already = $stock->quantityAlreadyInCart($_SESSION['panier'], $id);
                $available = $stock->availableBoisson($id);
                if ($already + $quantite > $available) {
                    return;
                }

                $personnalisation = (string) ($post['personnalisation_boisson'] ?? '');
                $prix = isset($item['prix_unitaire']) ? (float) $item['prix_unitaire'] : 2.50;
                $_SESSION['panier'][] = [
                    'type' => 'boisson',
                    'id' => $id,
                    'id_boisson' => $id,
                    'nom' => $item['nom_boisson'],
                    'prix' => $prix,
                    'quantite' => $quantite,
                    'sous_total' => $prix * $quantite,
                    'personnalisation' => $personnalisation,
                ];
            }
        }
    }

    public function modifyQuantity(int $index, string $action): void
    {
        $this->ensureCart();

        if (!isset($_SESSION['panier'][$index])) {
            return;
        }

        if ($action === 'plus') {
            $item = $_SESSION['panier'][$index];
            $idBoisson = (int) ($item['id_boisson'] ?? ($item['type'] === 'boisson' ? ($item['id'] ?? 0) : 0));
            $nextQty = (int) $item['quantite'] + 1;

            if ($idBoisson > 0) {
                $stock = StockService::fromApp();
                $alreadyOthers = $stock->quantityAlreadyInCart($_SESSION['panier'], $idBoisson, $index);
                if ($alreadyOthers + $nextQty > $stock->availableBoisson($idBoisson)) {
                    return;
                }
            }

            $_SESSION['panier'][$index]['quantite'] = $nextQty;
            $_SESSION['panier'][$index]['sous_total'] =
                $_SESSION['panier'][$index]['prix'] * $_SESSION['panier'][$index]['quantite'];
        } elseif ($action === 'minus' && $_SESSION['panier'][$index]['quantite'] > 1) {
            $_SESSION['panier'][$index]['quantite']--;
            $_SESSION['panier'][$index]['sous_total'] =
                $_SESSION['panier'][$index]['prix'] * $_SESSION['panier'][$index]['quantite'];
        }
    }

    public function removeItem(int $index): void
    {
        $this->ensureCart();

        if (isset($_SESSION['panier'][$index])) {
            array_splice($_SESSION['panier'], $index, 1);
        }
    }

    /**
     * @return array{
     *   panier: list<array<string,mixed>>,
     *   total_panier: float,
     *   nombre_articles: int,
     *   tva_rate: float,
     *   tva_amount: float,
     *   total_ttc: float
     * }
     */
    public function totals(?float $tvaRate = null): array
    {
        $this->ensureCart();

        $tvaRate ??= Application::getInstance()->moneyFormatter()->tvaRate();

        $total = 0.0;
        $count = 0;

        foreach ($_SESSION['panier'] as $item) {
            $total += (float) ($item['sous_total'] ?? 0);
            $count += (int) ($item['quantite'] ?? 0);
        }

        $tvaAmount = $total * $tvaRate;

        return [
            'panier' => $_SESSION['panier'],
            'total_panier' => $total,
            'nombre_articles' => $count,
            'tva_rate' => $tvaRate,
            'tva_amount' => $tvaAmount,
            'total_ttc' => $total + $tvaAmount,
        ];
    }

    public function clearCart(): void
    {
        $this->session->start();
        unset($_SESSION['panier'], $_SESSION['commande_confirmee'], $_SESSION['suivi_commande_id']);
    }

    public function clearConfirmedOrder(): void
    {
        $this->session->start();
        unset($_SESSION['commande_confirmee']);
    }
}
