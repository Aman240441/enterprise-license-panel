<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\RBACMiddleware;
use App\Services\AuditLoggerService;

class SettingsApiController
{
    /**
     * GET /api/v1/settings
     */
    public function getSettings(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();

        $rows = DatabaseConnection::fetchAll("SELECT * FROM `settings` ORDER BY setting_group ASC");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_group']][$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }

        ResponseHelper::success($settings, "Settings retrieved");
    }

    /**
     * POST /api/v1/settings/update
     */
    public function updateSettings(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('settings.manage');

        $body = $request->getBody();
        $settingsInput = $body['settings'] ?? [];

        if (empty($settingsInput) || !is_array($settingsInput)) {
            ResponseHelper::error("Invalid settings payload.", 400);
        }

        foreach ($settingsInput as $key => $val) {
            DatabaseConnection::query(
                "UPDATE `settings` SET `setting_value` = ?, `updated_at` = NOW() WHERE `setting_key` = ?",
                [(string)$val, (string)$key]
            );
        }

        AuditLoggerService::log(
            $currentUser['id'],
            'settings.update',
            "Updated platform system settings",
            'settings',
            null,
            $settingsInput,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "Settings updated successfully.");
    }
}
