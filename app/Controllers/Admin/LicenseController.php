<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Models\PlanModel;

class LicenseController
{
    /**
     * GET /licenses
     * Render License Registry List View
     */
    public function index(Request $request): void
    {
        $products = DatabaseConnection::fetchAll("SELECT * FROM `products` WHERE status = 'active' ORDER BY name ASC");
        $plans = DatabaseConnection::fetchAll("SELECT * FROM `license_plans` WHERE is_active = 1 ORDER BY name ASC");
        require __DIR__ . '/../../../views/licenses/index.php';
    }

    /**
     * GET /licenses/create
     * Render License Generator Form View
     */
    public function create(Request $request): void
    {
        $products = DatabaseConnection::fetchAll("SELECT * FROM `products` WHERE status = 'active' ORDER BY name ASC");
        $plans = PlanModel::getActivePlansForProduct();

        require __DIR__ . '/../../../views/licenses/create.php';
    }
}
