<?php

declare(strict_types=1);

namespace App\Controller\Api\Employe;

use App\Auth\StaffAuthService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\StaffSettingsService;

final class EmployeController
{
    private StaffAuthService $auth;
    private StaffSettingsService $settings;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->auth = $app->staffAuth();
        $this->settings = StaffSettingsService::fromApp($app);
    }

    public function handle(): void
    {
        $user = $this->auth->user();
        if ($user === null) {
            ApiResponse::error('Non authentifié', 401);
        }

        ApiResponse::json(['account' => $this->settings->staffAccount($user)]);
    }
}
