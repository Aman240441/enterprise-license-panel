<?php

namespace App\Services;

use App\Database\DatabaseConnection;
use App\Models\DeviceModel;
use Exception;

class DeviceManagerService
{
    /**
     * Process Client Hardware / Browser Device Activation
     */
    public static function activateDevice(array $license, array $clientData): array
    {
        $licenseId = (int) $license['id'];
        $fingerprint = trim($clientData['device_fingerprint']);
        $allowedDevices = (int) $license['allowed_devices'];

        // 1. Check if device is already registered for this license
        $existingDevice = DeviceModel::findByFingerprint($licenseId, $fingerprint);

        if ($existingDevice) {
            // Update last_seen timestamp and issue renewed session token
            $sessionToken = bin2hex(random_bytes(32));
            DeviceModel::updateLastSeen($existingDevice['id'], $sessionToken);

            $currentCount = DeviceModel::countActiveDevices($licenseId);
            DatabaseConnection::query("UPDATE `licenses` SET `current_devices` = ? WHERE id = ?", [$currentCount, $licenseId]);

            return [
                'activated'        => true,
                'is_existing'      => true,
                'device_id'        => (int) $existingDevice['id'],
                'session_token'    => $sessionToken,
                'allowed_devices'  => $allowedDevices,
                'current_devices'  => $currentCount
            ];
        }

        // 2. Check Device Limit Count for NEW Devices
        $currentCount = DeviceModel::countActiveDevices($licenseId);
        if ($currentCount >= $allowedDevices) {
            if ($allowedDevices === 1) {
                // For single-device allowed licenses, rebind the slot to the new device session
                DatabaseConnection::query("UPDATE `devices` SET `is_active` = 0 WHERE `license_id` = ?", [$licenseId]);
                $currentCount = 0;
            } else {
                throw new Exception("Activation Failed: Allowed device limit reached ({$currentCount}/{$allowedDevices} devices active). Please deactivate an existing device first.");
            }
        }

        // 3. Register New Device
        $sessionToken = bin2hex(random_bytes(32));
        $deviceId = DeviceModel::registerDevice([
            'license_id'         => $licenseId,
            'device_fingerprint' => $fingerprint,
            'browser'            => $clientData['browser'] ?? null,
            'os'                 => $clientData['os'] ?? null,
            'platform'           => $clientData['platform'] ?? null,
            'ip_address'         => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'country'            => $clientData['country'] ?? null,
            'city'               => $clientData['city'] ?? null,
            'session_token'      => $sessionToken
        ]);

        // Update license current_devices counter
        $newCount = $currentCount + 1;
        DatabaseConnection::query("UPDATE `licenses` SET `current_devices` = ? WHERE id = ?", [$newCount, $licenseId]);

        return [
            'activated'        => true,
            'is_existing'      => false,
            'device_id'        => $deviceId,
            'session_token'    => $sessionToken,
            'allowed_devices'  => $allowedDevices,
            'current_devices'  => $newCount
        ];
    }
}
