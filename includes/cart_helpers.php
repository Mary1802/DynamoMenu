<?php

/**
 * Clés panier et détection des boissons personnalisables.
 */

function cart_make_key(string $type, string $name, string $category = '', string $personalization = ''): string
{
    $payload = mb_strtolower(trim($type . '|' . $name . '|' . $category . '|' . $personalization));

    return $type . ':' . md5($payload);
}

function cart_find_index(array $panier, string $cartKey): ?int
{
    foreach ($panier as $i => $item) {
        if (($item['cart_key'] ?? '') === $cartKey) {
            return (int) $i;
        }
    }

    return null;
}

function cart_is_duplicate_plat(string $type, string $personalization): bool
{
    return in_array($type, ['menu_item', 'plat'], true) && trim($personalization) === '';
}

function cart_drink_kind(string $name): ?string
{
    $n = mb_strtolower(trim($name));
    if (in_array($n, ['jus de fruit', 'milkshake', 'cocktail de fruits'], true)) {
        return 'fruit';
    }
    if ($n === 'coca-cola, fanta, sprite' || (str_contains($n, 'coca') && str_contains($n, 'fanta') && str_contains($n, 'sprite'))) {
        return 'soda';
    }

    return null;
}

function cart_list_keys(array $panier): array
{
    $keys = [];
    foreach ($panier as $item) {
        if (!empty($item['cart_key'])) {
            $keys[] = $item['cart_key'];
        }
    }

    return $keys;
}
