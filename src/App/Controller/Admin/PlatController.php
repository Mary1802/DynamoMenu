<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Repository\BoissonRepository;
use App\Repository\PlatRepository;
use App\Service\MenuImageUploadService;
use PDOException;

final class PlatController
{
    private PlatRepository $plats;
    private BoissonRepository $boissons;
    private MenuImageUploadService $uploads;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->plats = $app->platRepository();
        $this->boissons = $app->boissonRepository();
        $this->uploads = $app->menuImageUploadService();
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return array{
     *   message: string,
     *   error: string,
     *   q: string,
     *   plats: list<array<string,mixed>>,
     *   boissons: list<array<string,mixed>>,
     *   categoriePlatOptions: list<string>,
     *   typesBoissonOptions: list<string>
     * }
     */
    public function handle(array $get, array $post, array $files): array
    {
        Application::getInstance()->schemaUpgrade()->run();

        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (isset($post['add_plat'])) {
                    $imagePath = $this->uploads->upload($files['image_plat'] ?? []);
                    $this->plats->create(
                        trim((string) $post['nom_plat']),
                        (float) $post['prix_unitaire'],
                        trim((string) ($post['categorie'] ?? '')),
                        (int) ($post['quantite_plat'] ?? 0),
                        $imagePath,
                        (int) ($post['temps_preparation_min'] ?? 15)
                    );
                    Application::getInstance()->activityLog()->log('plat_create', 'Plat ajouté : ' . $post['nom_plat'], 'admin');
                    $message = 'Plat ajouté.';
                }
                if (isset($post['update_plat'])) {
                    $imagePath = $this->uploads->upload($files['image_plat'] ?? []);
                    $existing = $this->plats->findById((int) $post['id_plat']);
                    $quantitePlat = (int) ($existing['quantite_plat'] ?? 0);
                    $this->plats->update(
                        (int) $post['id_plat'],
                        trim((string) $post['nom_plat']),
                        (float) $post['prix_unitaire'],
                        trim((string) ($post['categorie'] ?? '')),
                        $quantitePlat,
                        $imagePath,
                        (int) ($post['temps_preparation_min'] ?? 15)
                    );
                    $message = 'Plat mis à jour.';
                }
                if (isset($post['delete_plat'])) {
                    $this->plats->delete((int) $post['id_plat']);
                    $message = 'Plat supprimé.';
                }
                if (isset($post['add_boisson'])) {
                    $idType = $this->boissons->resolveTypeId((string) ($post['type_boisson'] ?? ''));
                    if ($idType === null) {
                        $error = 'Choisissez un type de boisson existant.';
                    } else {
                        $imagePath = $this->uploads->upload($files['image_boisson'] ?? []);
                        $this->boissons->create(
                            trim((string) $post['nom_boisson']),
                            $idType,
                            trim((string) ($post['dosage'] ?? '')),
                            (int) ($post['quantite_boisson'] ?? 0),
                            (float) ($post['prix_unitaire'] ?? 0),
                            trim((string) ($post['options_fruits'] ?? '')),
                            $imagePath
                        );
                        $message = 'Boisson ajoutée.';
                    }
                }
                if (isset($post['update_boisson'])) {
                    $idType = $this->boissons->resolveTypeId((string) ($post['type_boisson'] ?? ''));
                    if ($idType === null) {
                        $error = 'Choisissez un type de boisson existant.';
                    } else {
                        $imagePath = $this->uploads->upload($files['image_boisson'] ?? []);
                        $this->boissons->update(
                            (int) $post['id_boisson'],
                            trim((string) $post['nom_boisson']),
                            $idType,
                            trim((string) ($post['dosage'] ?? '')),
                            (int) ($post['quantite_boisson'] ?? 0),
                            (float) ($post['prix_unitaire'] ?? 0),
                            trim((string) ($post['options_fruits'] ?? '')),
                            $imagePath
                        );
                        $message = 'Boisson mise à jour.';
                    }
                }
                if (isset($post['delete_boisson'])) {
                    $this->boissons->delete((int) $post['id_boisson']);
                    $message = 'Boisson supprimée.';
                }
            } catch (PDOException $e) {
                $error = $e->getMessage();
            }
        }

        $q = trim((string) ($get['q'] ?? ''));

        return [
            'message' => $message,
            'error' => $error,
            'q' => $q,
            'plats' => $this->plats->findAll($q !== '' ? $q : null),
            'boissons' => $this->boissons->findAll($q !== '' ? $q : null),
            'categoriePlatOptions' => $this->plats->listCategories(),
            'typesBoissonOptions' => $this->boissons->listTypeNames(),
        ];
    }
}
