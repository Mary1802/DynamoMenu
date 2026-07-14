<?php

declare(strict_types=1);

namespace App\View;

use RuntimeException;

/** Rendu des templates PHP (couche présentation). */
final class View
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private readonly string $template,
        private readonly array $data = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function make(string $template, array $data = []): self
    {
        return new self($template, $data);
    }

    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        (new self($template, $data))->output();
    }

    public function output(): void
    {
        $path = __DIR__ . '/templates/' . $this->template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Template introuvable : ' . $this->template);
        }

        // Copie locale : extract() prend un tableau par référence (interdit sur readonly).
        $data = $this->data;
        extract($data, EXTR_SKIP);
        require $path;
    }
}
