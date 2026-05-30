<?php
/**
 * Indexe les images du menu (recherche récursive dans assets/images).
 *
 * @return array<string, string> clé = nom de fichier en minuscules, valeur = chemin relatif depuis client/
 */
function build_menu_image_index(string $imagesRoot, string $webPrefix = '../assets/images'): array
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

/**
 * Encode chaque segment d'un chemin relatif pour l'attribut src (espaces, accents).
 */
function encode_menu_image_path(string $path): string
{
    $parts = explode('/', $path);

    return implode('/', array_map(static function (string $segment): string {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            return $segment;
        }

        return rawurlencode($segment);
    }, $parts));
}
