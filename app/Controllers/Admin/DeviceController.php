<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;

class DeviceController
{
    /**
     * GET /devices
     * Render Device Management Dashboard View
     */
    public function index(Request $request): void
    {
        $products = DatabaseConnection::fetchAll("SELECT * FROM `products` WHERE status = 'active' ORDER BY name ASC");
        
        $totalDevices = (int) (DatabaseConnection::fetchOne("SELECT COUNT(*) as cnt FROM `devices` WHERE is_active = 1")['cnt'] ?? 0);
        $totalLicensesWithDevices = (int) (DatabaseConnection::fetchOne("SELECT COUNT(DISTINCT license_id) as cnt FROM `devices` WHERE is_active = 1")['cnt'] ?? 0);

        require __DIR__ . '/../../../views/devices/index.php';
    }
}
