<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;

class UserController
{
    /**
     * GET /employees
     */
    public function index(Request $request): void
    {
        $roles = DatabaseConnection::fetchAll("SELECT * FROM `roles` ORDER BY id ASC");
        $plans = DatabaseConnection::fetchAll("SELECT * FROM `license_plans` WHERE is_active = 1 ORDER BY price ASC");
        $permissions = DatabaseConnection::fetchAll("SELECT * FROM `permissions` ORDER BY category ASC, name ASC");

        require __DIR__ . '/../../../views/employees/index.php';
    }
}
