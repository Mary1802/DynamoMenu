<?php

declare(strict_types=1);

namespace App\View\Client;

use App\Core\Application;
use App\View\View;

final class ClientFooterView
{
    public static function render(): void
    {
        $app = Application::getInstance();
        $tables = $app->tableContextService();

        $contactRows = $app->contactRepository()->listAll();
        if ($contactRows === []) {
            $appConfig = $app->config()->app();
            if (is_array($appConfig['contacts'] ?? null) && $appConfig['contacts'] !== []) {
                $contactRows = [$appConfig['contacts']];
            }
        }

        $contact = self::primaryContact($contactRows);
        $horairesLines = $app->horairesRepository()->lines();

        View::render('client/footer', [
            'year' => (int) date('Y'),
            'homeHref' => $tables->link('index.php'),
            'menuHref' => $tables->link('menu.php'),
            'aboutHref' => $tables->link('index.php') . '#apropos',
            'horairesLines' => $horairesLines,
            'contact' => $contact,
            'hasInfo' => $horairesLines !== [] || $contact !== null,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $contactRows
     * @return array<string, string>|null
     */
    private static function primaryContact(array $contactRows): ?array
    {
        foreach ($contactRows as $row) {
            $nom = trim((string) ($row['nom'] ?? $row['nom_etablissement'] ?? 'DynamoMenu'));
            $infos = trim((string) ($row['infos'] ?? $row['description'] ?? ''));
            $adresse = trim((string) ($row['adresse'] ?? ''));
            $tel = trim((string) ($row['telephone'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $whatsapp = trim((string) ($row['whatsapp'] ?? ''));

            if ($nom === '' && $adresse === '' && $tel === '' && $email === '' && $whatsapp === '') {
                continue;
            }

            if ($infos === '') {
                $infos = 'Restaurant avec service sur place. Commandez depuis votre table via le menu digital.';
            }

            return [
                'nom' => $nom,
                'infos' => $infos,
                'adresse' => $adresse,
                'telephone' => $tel,
                'email' => $email,
                'whatsapp' => $whatsapp,
            ];
        }

        return null;
    }
}
