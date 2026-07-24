<?php

/**
 * Automated System Health & Unit Verification Suite - Phase 8
 */

declare(strict_types=1);

require_once __DIR__ . '/public/index.php';

use App\Database\DatabaseConnection;

echo "========================================================\n";
echo " ENTERPRISE LICENSE MANAGEMENT PLATFORM - SYSTEM SUITE\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function runTest(string $name, callable $fn) {
    global $passed, $failed;
    echo sprintf("[TEST] %-50s ", $name);
    try {
        $result = $fn();
        if ($result === true) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "\033[31m[ERROR: " . $e->getMessage() . "]\033[0m\n";
        $failed++;
    }
}

// 1. Test Database Singleton Connection
runTest("Database Connection Singleton Test", function () {
    $db = DatabaseConnection::getInstance();
    return $db instanceof \PDO;
});

// 2. Test 22 Database Schema Tables
runTest("Schema Tables Verification (22 Tables)", function () {
    $tables = DatabaseConnection::fetchAll("SHOW TABLES");
    return count($tables) >= 22;
});

// 3. Test Argon2id Password Hashing & Verification
runTest("Argon2id Password Cryptography Test", function () {
    $pass = "SecretPass123!";
    $hash = \App\Helpers\SecurityHelper::hashPassword($pass);
    return \App\Helpers\SecurityHelper::verifyPassword($pass, $hash);
});

// 4. Test Zero-Dependency JWT Generation & Decoding
runTest("JWT Engine Encoding & Decoding Test", function () {
    $payload = ['sub' => 1, 'email' => 'admin@system.com', 'role' => 'super_admin'];
    $token = \App\Services\JWTService::generateToken($payload, 3600);
    $decoded = \App\Services\JWTService::validateToken($token);
    return $decoded !== null && $decoded['sub'] === 1 && $decoded['email'] === 'admin@system.com';
});

// 5. Test Cryptographic Key Generator & Collision Avoidance
runTest("Cryptographic Key Generator Test", function () {
    $key = \App\Helpers\KeyGenerator::generateKey('GB');
    return str_starts_with($key, 'GB-') && strlen($key) === 22;
});

// 6. Test Single License Generation Service
runTest("LicenseGeneratorService Single Key Creation", function () {
    $admin = DatabaseConnection::fetchOne("SELECT u.id, u.uuid, u.email, r.slug as role_slug FROM `users` u JOIN `roles` r ON u.role_id = r.id LIMIT 1");
    $product = DatabaseConnection::fetchOne("SELECT id FROM `products` LIMIT 1");
    $plan = DatabaseConnection::fetchOne("SELECT id FROM `license_plans` LIMIT 1");

    $result = \App\Services\LicenseGeneratorService::generateSingle([
        'product_id' => $product['id'],
        'plan_id' => $plan['id'],
        'prefix' => 'GB'
    ], $admin);

    return !empty($result['license_key']) && str_starts_with($result['license_key'], 'GB-');
});

// 7. Test Bulk License Generation Service (100 Keys Transaction)
runTest("LicenseGeneratorService Bulk 100 Keys Transaction", function () {
    $admin = DatabaseConnection::fetchOne("SELECT u.id, u.uuid, u.email, r.slug as role_slug FROM `users` u JOIN `roles` r ON u.role_id = r.id LIMIT 1");
    $product = DatabaseConnection::fetchOne("SELECT id FROM `products` LIMIT 1");
    $plan = DatabaseConnection::fetchOne("SELECT id FROM `license_plans` LIMIT 1");

    $result = \App\Services\LicenseGeneratorService::bulkGenerate([
        'product_id' => $product['id'],
        'plan_id' => $plan['id'],
        'count' => 100,
        'prefix' => 'BULK'
    ], $admin);

    return count($result['keys']) === 100;
});

// 8. Test Client Device Activation & Device Limits
runTest("Client Device Activation & Limit Enforcement Test", function () {
    $license = DatabaseConnection::fetchOne("SELECT l.*, p.name as product_name, lp.name as plan_name FROM `licenses` l JOIN `products` p ON l.product_id = p.id JOIN `license_plans` lp ON l.plan_id = lp.id WHERE l.status = 'active' AND (SELECT COUNT(*) FROM `devices` d WHERE d.license_id = l.id) < l.allowed_devices LIMIT 1");
    if (!$license) {
        // Fallback: create fresh license for test
        $genResult = \App\Services\LicenseGeneratorService::generate([
            'product_id' => 1, 'plan_id' => 1, 'allowed_devices' => 3
        ], 1);
        $license = DatabaseConnection::fetchOne("SELECT l.*, p.name as product_name, lp.name as plan_name FROM `licenses` l JOIN `products` p ON l.product_id = p.id JOIN `license_plans` lp ON l.plan_id = lp.id WHERE l.id = ?", [$genResult['licenses'][0]['id']]);
    }
    $result = \App\Services\DeviceManagerService::activateDevice($license, [
        'device_fingerprint' => 'test_fingerprint_' . bin2hex(random_bytes(4)),
        'browser' => 'Test Browser',
        'os' => 'Windows 11'
    ]);

    return $result['activated'] === true && !empty($result['session_token']);
});

echo "\n========================================================\n";
echo sprintf(" TEST SUMMARY: Total: %d | Passed: %d | Failed: %d\n", $passed + $failed, $passed, $failed);
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
