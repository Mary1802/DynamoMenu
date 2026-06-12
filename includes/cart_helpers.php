<?php

/**
 * Pont procédural → App\Service\CartService (POO).
 */

require_once __DIR__ . '/bootstrap.php';

use App\Service\CartService;

function cart_make_key(string $type, string $name, string $category = '', string $personalization = ''): string
{
    return CartService::makeKey($type, $name, $category, $personalization);
}

function cart_find_index(array $panier, string $cartKey): ?int
{
    return CartService::indexIn($panier, $cartKey);
}

function cart_is_duplicate_plat(string $type, string $personalization): bool
{
    return app()->cartService()->isDuplicatePlat($type, $personalization);
}

function cart_drink_kind(string $name): ?string
{
    return app()->cartService()->drinkKind($name);
}

function cart_list_keys(array $panier): array
{
    return CartService::keysFrom($panier);
}
