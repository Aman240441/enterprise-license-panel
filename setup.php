<?php

/**
 * CLI Setup & Database Migration Runner - Enterprise Edition
 */

declare(strict_types=1);

require_once __DIR__ . '/public/index.php';

echo "========================================================\n";
echo " ENTERPRISE LICENSE MANAGEMENT SYSTEM - SYSTEM SETUP\n";
echo "========================================================\n";

try {
    echo "[1/2] Creating 22 Normalized Enterprise Database Tables (Manual Generation Model)...\n";
    \App\Database\Schema::up();
    echo "      -> Database Schema (22 Tables) successfully verified and migrated.\n";

    echo "[2/2] Seeding Roles, Reseller Tier, Permissions, Default Products, Plans & Super Admin...\n";
    \App\Database\Seeder::run();
    echo "      -> Enterprise seeds successfully inserted.\n";

    echo "\nSystem setup completed with 0 errors.\n";
    echo "Super Admin Credentials:\n";
    echo "Email: admin@system.com\n";
    echo "Password: Admin@123456\n";
    echo "API Version: /api/v1/\n";
    echo "OpenAPI Documentation: public/openapi.json\n";
} catch (\Throwable $e) {
    echo "\n[ERROR] Setup failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
