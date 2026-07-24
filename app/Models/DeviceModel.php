<?php

namespace App\Models;

use App\Database\DatabaseConnection;
use App\Helpers\SecurityHelper;

class DeviceModel
{
    public static function findByFingerprint(int $licenseId, string $fingerprint): ?array
    {
        $cleanFingerprint = strtoupper(trim($fingerprint));
        return DatabaseConnection::fetchOne("
            SELECT * FROM `devices` WHERE `license_id` = ? AND UPPER(TRIM(`device_fingerprint`)) = ? AND `is_active` = 1
        ", [$licenseId, $cleanFingerprint]);
    }

    public static function getActiveDevicesForLicense(int $licenseId): array
    {
        return DatabaseConnection::fetchAll("
            SELECT * FROM `devices` WHERE `license_id` = ? AND `is_active` = 1 ORDER BY `last_seen` DESC
        ", [$licenseId]);
    }

    public static function countActiveDevices(int $licenseId): int
    {
        $row = DatabaseConnection::fetchOne("
            SELECT COUNT(*) as cnt FROM `devices` WHERE `license_id` = ? AND `is_active` = 1
        ", [$licenseId]);
        return (int) ($row['cnt'] ?? 0);
    }

    public static function registerDevice(array $data): int
    {
        $uuid = SecurityHelper::generateUuid();
        $sessionTokenHash = SecurityHelper::sha256($data['session_token'] ?? random_bytes(16));

        DatabaseConnection::query("
            INSERT INTO `devices` 
            (`uuid`, `license_id`, `device_fingerprint`, `browser`, `os`, `platform`, `ip_address`, `country`, `city`, `session_token_hash`, `is_active`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ", [
            $uuid,
            $data['license_id'],
            $data['device_fingerprint'],
            $data['browser'] ?? null,
            $data['os'] ?? null,
            $data['platform'] ?? null,
            $data['ip_address'] ?? null,
            $data['country'] ?? null,
            $data['city'] ?? null,
            $sessionTokenHash
        ]);

        return (int) DatabaseConnection::lastInsertId();
    }

    public static function updateLastSeen(int $deviceId, ?string $sessionToken = null): void
    {
        if ($sessionToken !== null) {
            $hash = SecurityHelper::sha256($sessionToken);
            DatabaseConnection::query("
                UPDATE `devices` SET `last_seen` = NOW(), `session_token_hash` = ? WHERE `id` = ?
            ", [$hash, $deviceId]);
        } else {
            DatabaseConnection::query("UPDATE `devices` SET `last_seen` = NOW() WHERE `id` = ?", [$deviceId]);
        }
    }

    public static function deactivateDevice(int $deviceId): bool
    {
        return DatabaseConnection::execute("UPDATE `devices` SET `is_active` = 0 WHERE `id` = ?", [$deviceId]) > 0;
    }

    public static function resetDevicesForLicense(int $licenseId): int
    {
        $affected = DatabaseConnection::execute("DELETE FROM `devices` WHERE `license_id` = ?", [$licenseId]);
        DatabaseConnection::execute("UPDATE `licenses` SET `current_devices` = 0 WHERE `id` = ?", [$licenseId]);
        return $affected;
    }
}
