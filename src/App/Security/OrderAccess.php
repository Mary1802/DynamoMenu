<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Application;
use App\Core\Config;

final class OrderAccess
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function grant(int $numCommande): void
    {
        if ($numCommande <= 0) {
            return;
        }

        if (!isset($_SESSION['order_access']) || !is_array($_SESSION['order_access'])) {
            $_SESSION['order_access'] = [];
        }

        if (!in_array($numCommande, $_SESSION['order_access'], true)) {
            $_SESSION['order_access'][] = $numCommande;
        }

        if (count($_SESSION['order_access']) > 20) {
            $_SESSION['order_access'] = array_slice($_SESSION['order_access'], -20);
        }
    }

    public function token(int $numCommande): string
    {
        return hash_hmac('sha256', (string) $numCommande, $this->config->secret());
    }

    public function verifyToken(int $numCommande, string $token): bool
    {
        return hash_equals($this->token($numCommande), $token);
    }

    /**
     * @param array<string, mixed> $commande
     */
    public function canAccess(array $commande, ?string $token = null): bool
    {
        $num = (int) ($commande['num_commande'] ?? 0);
        if ($num <= 0) {
            return false;
        }

        if (!empty($_SESSION['suivi_commande_id']) && (int) $_SESSION['suivi_commande_id'] === $num) {
            return true;
        }

        if (!empty($_SESSION['order_access']) && is_array($_SESSION['order_access']) && in_array($num, $_SESSION['order_access'], true)) {
            return true;
        }

        $ctx = Application::getInstance()->tableContextService()->session();
        if ($ctx !== null && (int) ($commande['num_table'] ?? 0) === (int) $ctx['num_table']) {
            return true;
        }

        if ($token !== null && $token !== '' && $this->verifyToken($num, $token)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $commande
     */
    public function requireAccess(array $commande, ?string $token = null): void
    {
        if (!$this->canAccess($commande, $token)) {
            http_response_code(403);
            header('Location: index.php?err=access');
            exit;
        }
    }
}
