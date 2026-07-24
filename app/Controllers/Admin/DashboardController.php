<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;

class DashboardController
{
    /**
     * GET /dashboard
     * Render Executive Admin Dashboard View
     */
    public function index(Request $request): void
    {
        $siteName = $_ENV['APP_NAME'] ?? 'Enterprise License Manager';
        require __DIR__ . '/../../../views/dashboard/index.php';
    }
}
