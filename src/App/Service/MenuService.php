<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Data\MenuSeed;
use App\Repository\BoissonRepository;
use App\Support\MenuImageIndex;
use PDO;
use PDOException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MenuService
{
    private const IMAGE_PLACEHOLDER = 'https://images.unsplash.com/photo-1525755662778-989d0524087e?auto=format&fit=crop&w=800&q=80';

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $projectRoot,
    ) {
    }

    public function imagePlaceholder(): string
    {
        return self::IMAGE_PLACEHOLDER;
    }

    /** @return array<string, string> */
    public function buildImageIndex(): array
    {
        return MenuImageIndex::build($this->projectRoot . '/assets/images');
    }

    public function seedStaticItems(): void
    {
        try {
            foreach (MenuSeed::items() as $seed) {
                [$sname, $scat, $sprice, $simg, $stype] = $seed;
                $imgPath = $simg ? $this->normalizeImagePath($this->findImagePathInAssets($simg)) : null;

                if ($stype === 'plat') {
                    $stmt = $this->pdo->prepare('SELECT categorie, image_url FROM plat WHERE nom_plat = ?');
                    $stmt->execute([$sname]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        $stmt2 = $this->pdo->prepare(
                            'INSERT INTO plat (nom_plat, prix_unitaire, categorie, image_url) VALUES (?, ?, ?, ?)'
                        );
                        $stmt2->execute([$sname, $sprice, $scat, $imgPath]);
                    } else {
                        $currentCategory = trim((string) ($existing['categorie'] ?? ''));
                        $currentImage = trim((string) ($existing['image_url'] ?? ''));
                        if ($currentCategory !== $scat || ($currentImage === '' && $imgPath !== null)) {
                            $stmt2 = $this->pdo->prepare(
                                'UPDATE plat SET categorie = ?, image_url = COALESCE(NULLIF(?, \'\'), image_url) WHERE nom_plat = ?'
                            );
                            $stmt2->execute([$scat, $imgPath, $sname]);
                        }
                    }
                } else {
                    $stmt = $this->pdo->prepare('SELECT image_url FROM boisson WHERE nom_boisson = ?');
                    $stmt->execute([$sname]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        $typeId = $this->defaultTypeIdForSeedDrink($sname);
                        $fruitOpts = $this->defaultFruitOptionsForSeedDrink($sname);
                        $stmt2 = $this->pdo->prepare(
                            'INSERT INTO boisson (nom_boisson, id_type, dosage, quantite_boisson, prix_unitaire, options_fruits, image_url)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt2->execute([$sname, $typeId, '', 50, $sprice, $fruitOpts, $imgPath]);
                    } elseif (trim((string) ($existing['image_url'] ?? '')) === '' && $imgPath !== null) {
                        $stmt2 = $this->pdo->prepare('UPDATE boisson SET image_url = ? WHERE nom_boisson = ?');
                        $stmt2->execute([$imgPath, $sname]);
                    }
                }
            }
        } catch (PDOException) {
            // ignore import errors
        }
    }

    /** @return list<array<string, mixed>> */
    public function buildMenuItems(): array
    {
        $menuItems = [];

        Application::getInstance()->schemaUpgrade()->run();

        try {
            $stmt = $this->pdo->query(
                'SELECT id_plat, nom_plat, prix_unitaire, categorie, image_url, temps_preparation_min FROM plat ORDER BY categorie, nom_plat'
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $menuItems[] = [
                    'category' => $r['categorie'] ?: 'Plats principaux',
                    'name' => $r['nom_plat'],
                    'desc' => '',
                    'price' => isset($r['prix_unitaire']) ? (float) $r['prix_unitaire'] : 0.0,
                    'img' => MenuImageIndex::normalizePath($r['image_url'] ?: null),
                    'prep_min' => (int) ($r['temps_preparation_min'] ?? 15),
                    'id_plat' => (int) $r['id_plat'],
                    'id_boisson' => null,
                    'stock' => null,
                    'available' => true,
                ];
            }

            $boissonCols = array_column(
                $this->pdo->query('SHOW COLUMNS FROM boisson')->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );
            $typeBoissonTableExists = count(
                $this->pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)
            ) > 0;

            if (in_array('id_type', $boissonCols, true) && $typeBoissonTableExists) {
                $fruitCol = in_array('options_fruits', $boissonCols, true) ? ', b.options_fruits' : '';
                $stmt = $this->pdo->query(
                    "SELECT b.id_boisson, b.nom_boisson, COALESCE(tb.nom_type, 'soda') AS type_boisson,
                            b.dosage, b.quantite_boisson, b.prix_unitaire, b.image_url{$fruitCol}
                     FROM boisson b
                     LEFT JOIN type_boisson tb ON b.id_type = tb.id_type
                     ORDER BY COALESCE(tb.nom_type, 'soda'), b.nom_boisson"
                );
            } else {
                $fruitCol = in_array('options_fruits', $boissonCols, true) ? ', options_fruits' : '';
                $stmt = $this->pdo->query(
                    "SELECT id_boisson, nom_boisson, 'soda' AS type_boisson, dosage, quantite_boisson,
                            prix_unitaire, image_url{$fruitCol}
                     FROM boisson ORDER BY nom_boisson"
                );
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $typeLabel = trim((string) ($r['type_boisson'] ?? ''));
                $fruitOptions = \App\Repository\BoissonRepository::parseFruitOptions(
                    isset($r['options_fruits']) ? (string) $r['options_fruits'] : ''
                );
                $descParts = array_filter([
                    trim((string) ($r['dosage'] ?? '')),
                    $typeLabel,
                    $fruitOptions !== [] ? ('Goûts : ' . implode(', ', $fruitOptions)) : '',
                ], static fn (string $p): bool => $p !== '');
                $stock = (int) ($r['quantite_boisson'] ?? 0);
                $menuItems[] = [
                    'category' => 'Boissons',
                    'type' => $typeLabel,
                    'name' => $r['nom_boisson'],
                    'desc' => implode(' — ', $descParts),
                    'price' => isset($r['prix_unitaire']) ? (float) $r['prix_unitaire'] : 0.0,
                    'img' => MenuImageIndex::normalizePath($r['image_url'] ?: null),
                    'prep_min' => 2,
                    'id_plat' => null,
                    'id_boisson' => (int) $r['id_boisson'],
                    'stock' => $stock,
                    'available' => $stock > 0,
                    'fruit_options' => $fruitOptions,
                ];
            }
        } catch (PDOException) {
            // keep empty on error
        }

        return $menuItems;
    }

    private function defaultFruitOptionsForSeedDrink(string $name): string
    {
        return match ($name) {
            'Jus de Fruit', 'Milkshake', 'Cocktail de Fruits'
                => 'Orange,Banane,Pomme,Ananas,Mangue,Fraise',
            'Smoothie Banane' => 'Banane,Fraise,Mangue,Ananas',
            default => '',
        };
    }

    private function defaultTypeIdForSeedDrink(string $name): int
    {
        $byName = [
            'Jus de Fruit' => 'jus',
            'Cocktail de Fruits' => 'jus',
            'Smoothie Banane' => 'jus',
            'Milkshake' => 'soda',
            'Coca-Cola, Fanta, Sprite' => 'soda',
            'Eau Minérale' => 'eau',
            'Pinacolada' => 'alcool',
            'Mojito' => 'alcool',
            'Jack Daniels' => 'alcool',
            'Red Label' => 'alcool',
            'Heinekein' => 'alcool',
        ];

        $typeName = $byName[$name] ?? 'soda';
        $id = Application::getInstance()->boissonRepository()->resolveTypeId($typeName);

        return $id ?? 1;
    }

    private function findImagePathInAssets(string $name): ?string
    {
        $base = $this->projectRoot . '/assets/images';
        if (!is_dir($base)) {
            return null;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
        foreach ($it as $f) {
            if (!$f->isFile()) {
                continue;
            }
            if (strcasecmp($f->getFilename(), $name) === 0) {
                $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($this->projectRoot) + 1));

                return $rel;
            }
        }

        return null;
    }

    private function normalizeImagePath(?string $path): ?string
    {
        return MenuImageIndex::normalizePath($path);
    }
}
