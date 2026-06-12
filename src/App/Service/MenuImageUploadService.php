<?php

declare(strict_types=1);

namespace App\Service;

final class MenuImageUploadService
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
    }

    /** @param array<string, mixed> $file */
    public function upload(array $file): ?string
    {
        if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'] ?? '', $allowed, true)) {
            return null;
        }

        $uploadDir = $this->projectRoot . '/assets/images/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $basename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo((string) $file['name'], PATHINFO_FILENAME));
        $filename = $basename . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            return null;
        }

        return 'assets/images/uploads/' . $filename;
    }
}
