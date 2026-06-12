<?php

declare(strict_types=1);

namespace App\View;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/templates/' . $template . '.php';
    }
}
