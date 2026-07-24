<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;

class SettingsController
{
    /**
     * GET /settings
     */
    public function index(Request $request): void
    {
        $settingsRows = DatabaseConnection::fetchAll("SELECT * FROM `settings` ORDER BY setting_group ASC");
        require __DIR__ . '/../../../views/settings/index.php';
    }
}
