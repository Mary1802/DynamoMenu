<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class BoissonRepository extends BaseRepository
{
    /** @return list<array<string, mixed>> */
    public function findAll(?string $query = null): array
    {
        $boissonCols = array_column(
            $this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        $typeTableExists = count(
            $this->pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)
        ) > 0;

        if ($typeTableExists && in_array('id_type', $boissonCols, true)) {
            $baseSql = 'SELECT b.*, tb.nom_type AS type_boisson FROM boisson b LEFT JOIN type_boisson tb ON b.id_type = tb.id_type';
            if ($query !== null && $query !== '') {
                $pattern = '%' . $query . '%';
                $stmt = $this->pdo->prepare($baseSql . ' WHERE nom_boisson LIKE ? ORDER BY nom_boisson');
                $stmt->execute([$pattern]);

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $this->pdo->query($baseSql . ' ORDER BY nom_boisson')->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($query !== null && $query !== '') {
            $pattern = '%' . $query . '%';
            $stmt = $this->pdo->prepare(
                'SELECT * FROM boisson WHERE nom_boisson LIKE ? OR dosage LIKE ? OR options_fruits LIKE ? ORDER BY nom_boisson'
            );
            $stmt->execute([$pattern, $pattern, $pattern]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->pdo->query('SELECT * FROM boisson ORDER BY nom_boisson')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resolveTypeId(string $nomType): ?int
    {
        $nomType = trim($nomType);
        if ($nomType === '') {
            return null;
        }

        $aliases = [
            'alcoolise' => 'alcool',
            'alcoolisé' => 'alcool',
            'alcoolisée' => 'alcool',
            'alcohol' => 'alcool',
            'naturelle' => 'jus',
            'naturel' => 'jus',
            'juice' => 'jus',
            'soft' => 'soda',
            'gazeux' => 'soda',
            'water' => 'eau',
        ];

        $normalized = mb_strtolower($nomType, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $ascii = is_string($ascii) ? strtolower(preg_replace('/[^a-z]/', '', $ascii) ?? '') : preg_replace('/[^a-z]/', '', $normalized) ?? '';

        if (isset($aliases[$normalized])) {
            $nomType = $aliases[$normalized];
        } elseif (isset($aliases[$ascii])) {
            $nomType = $aliases[$ascii];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_type FROM type_boisson WHERE LOWER(TRIM(nom_type)) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([$nomType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id_type'] : null;
    }

    public function create(
        string $nom,
        int $idType,
        string $dosage,
        int $quantite,
        float $prix,
        string $optionsFruits,
        ?string $imagePath
    ): void {
        $optionsFruits = $this->normalizeFruitOptions($optionsFruits);
        $stmt = $this->pdo->prepare(
            'INSERT INTO boisson (nom_boisson, id_type, dosage, quantite_boisson, prix_unitaire, options_fruits, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $idType, $dosage, $quantite, $prix, $optionsFruits, $imagePath]);
    }

    public function update(
        int $id,
        string $nom,
        int $idType,
        string $dosage,
        int $quantite,
        float $prix,
        string $optionsFruits,
        ?string $imagePath
    ): void {
        $optionsFruits = $this->normalizeFruitOptions($optionsFruits);
        if ($imagePath !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE boisson SET nom_boisson = ?, id_type = ?, dosage = ?, quantite_boisson = ?, prix_unitaire = ?,
                 options_fruits = ?, image_url = ? WHERE id_boisson = ?'
            );
            $stmt->execute([$nom, $idType, $dosage, $quantite, $prix, $optionsFruits, $imagePath, $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE boisson SET nom_boisson = ?, id_type = ?, dosage = ?, quantite_boisson = ?, prix_unitaire = ?,
                 options_fruits = ? WHERE id_boisson = ?'
            );
            $stmt->execute([$nom, $idType, $dosage, $quantite, $prix, $optionsFruits, $id]);
        }
    }

    /** Normalise "orange, pomme ; ananas" → "Orange,Pomme,Ananas". */
    public function normalizeFruitOptions(string $raw): string
    {
        $parts = preg_split('/[,;|]+/', $raw) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $clean[] = mb_convert_case(mb_strtolower($part, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return implode(',', array_values(array_unique($clean)));
    }

    /** @return list<string> */
    public static function parseFruitOptions(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[,;|]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $out[] = mb_convert_case(mb_strtolower($part, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return array_values(array_unique($out));
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM boisson WHERE id_boisson = ?');
        $stmt->execute([$id]);
    }

    /** @return list<string> */
    public function listTypeNames(): array
    {
        $canonical = ['soda', 'eau', 'jus', 'alcool'];

        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'type_boisson'");
            if ($check === false || $check->fetchColumn() === false) {
                return $canonical;
            }
            $stmt = $this->pdo->query('SELECT nom_type FROM type_boisson ORDER BY nom_type ASC');
            $existing = array_values(array_filter(
                array_map(static fn ($row): string => trim((string) $row), $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []),
                static fn (string $name): bool => $name !== ''
            ));

            $byLower = [];
            foreach ($existing as $name) {
                $byLower[mb_strtolower($name, 'UTF-8')] = $name;
            }

            $ordered = [];
            foreach ($canonical as $want) {
                $ordered[] = $byLower[$want] ?? $want;
            }

            return $ordered;
        } catch (PDOException) {
            return $canonical;
        }
    }
}
