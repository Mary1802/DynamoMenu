<?php

declare(strict_types=1);

namespace App\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MenuImageIndex
{
    /**
     * @return array<string, string> clé = nom de fichier en minuscules, valeur = chemin web relatif
     */
    public static function build(string $imagesRoot, string $webPrefix = '../assets/images'): array
    {
        $index = [];

        if (!is_dir($imagesRoot)) {
            return $index;
        }

        $imagesRoot = rtrim(str_replace('\\', '/', realpath($imagesRoot) ?: $imagesRoot), '/');
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imagesRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $allowed, true)) {
                continue;
            }

            $basename = strtolower($file->getFilename());
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $relativeInsideImages = substr($fullPath, strlen($imagesRoot) + 1);
            $webPath = rtrim($webPrefix, '/') . '/' . $relativeInsideImages;

            if (!isset($index[$basename])) {
                $index[$basename] = $webPath;
            }
        }

        return $index;
    }

    public static function normalizePath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return $path;
        }

        $path = str_replace('\\', '/', trim($path));

        return (string) preg_replace('#assets/images/kombo/#i', 'assets/images/combo/', $path);
    }

    public static function encodePath(string $path): string
    {
        $parts = explode('/', $path);

        return implode('/', array_map(static function (string $segment): string {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return $segment;
            }

            return rawurlencode($segment);
        }, $parts));
    }
}
