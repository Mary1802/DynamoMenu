<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Service\StaffSettingsService;

final class ParametresController
{
    private StaffSettingsService $settings;

    public function __construct(?StaffSettingsService $settings = null)
    {
        $this->settings = $settings ?? StaffSettingsService::fromApp();
    }

    /**
     * @param array<string, mixed> $user
     * @return array{account:array<string,mixed>,contacts:array<string,mixed>}
     */
    public function index(array $user): array
    {
        return [
            'account' => $this->settings->staffAccount($user),
            'contacts' => $this->settings->primaryContact(),
        ];
    }
}
