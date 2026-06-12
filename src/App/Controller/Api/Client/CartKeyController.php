<?php

declare(strict_types=1);

namespace App\Controller\Api\Client;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\CartService;

final class CartKeyController
{
    public function handle(array $get, array $post): void
    {
        $name = trim((string) ($get['name'] ?? $post['name'] ?? ''));
        $category = trim((string) ($get['category'] ?? $post['category'] ?? ''));
        $type = trim((string) ($get['type'] ?? $post['type'] ?? 'menu_item'));
        $personnalisation = trim((string) ($get['personnalisation'] ?? $post['personnalisation'] ?? ''));

        ApiResponse::json([
            'key' => CartService::makeKey($type, $name, $category, $personnalisation),
        ]);
    }
}
