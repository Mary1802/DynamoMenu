<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class PlatRepository extends BaseRepository
{
    /** @return list<array<string, mixed>> */
    public function findAll(?string $query = null): array
    {
        if ($query !== null && $query !== '') {
            $pattern = '%' . $query . '%';
            $stmt = $this->pdo->prepare(
                'SELECT * FROM plat WHERE nom_plat LIKE ? OR categorie LIKE ? ORDER BY categorie, nom_plat'
            );
            $stmt->execute([$pattern, $pattern]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->pdo->query('SELECT * FROM plat ORDER BY categorie, nom_plat')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $nom, float $prix, string $categorie, int $quantite, ?string $imagePath): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO plat (nom_plat, prix_unitaire, categorie, quantite_plat, image_url) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $prix, $categorie, $quantite, $imagePath]);
    }

    public function update(int $id, string $nom, float $prix, string $categorie, int $quantite, ?string $imagePath): void
    {
        if ($imagePath !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE plat SET nom_plat = ?, prix_unitaire = ?, categorie = ?, quantite_plat = ?, image_url = ? WHERE id_plat = ?'
            );
            $stmt->execute([$nom, $prix, $categorie, $quantite, $imagePath, $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE plat SET nom_plat = ?, prix_unitaire = ?, categorie = ?, quantite_plat = ? WHERE id_plat = ?'
            );
            $stmt->execute([$nom, $prix, $categorie, $quantite, $id]);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM plat WHERE id_plat = ?');
        $stmt->execute([$id]);
    }

    /** @return list<string> */
    public function listCategories(): array
    {
        $defaults = [
            'Apéritifs', 'Entrées', 'Plats principaux', 'Combo',
            'Accompagnements', 'Desserts', 'Boissons',
        ];

        $fromDb = [];
        try {
            $stmt = $this->pdo->query(
                "SELECT DISTINCT TRIM(categorie) AS categorie FROM plat
                 WHERE categorie IS NOT NULL AND TRIM(categorie) <> ''
                 ORDER BY categorie"
            );
            $fromDb = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (PDOException) {
            // defaults only
        }

        $merged = [];
        foreach (array_merge($defaults, $fromDb) as $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $merged[$label] = true;
            }
        }

        $list = array_keys($merged);
        natcasesort($list);

        return array_values($list);
    }
}
