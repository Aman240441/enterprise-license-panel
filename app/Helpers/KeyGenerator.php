<?php

namespace App\Helpers;

use App\Database\DatabaseConnection;
use Exception;

class KeyGenerator
{
    // Character set excluding easily confused characters like O, 0, I, 1
    private const CHARSET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Generate a single cryptographically secure formatted license key
     * Format: PREFIX-XXXX-XXXX-XXXX-XXXX
     */
    public static function generateKey(string $prefix = 'GB', int $blocks = 4, int $blockSize = 4): string
    {
        $prefix = strtoupper(trim($prefix));
        if (empty($prefix)) {
            $prefix = 'GB';
        }

        $parts = [];
        for ($i = 0; $i < $blocks; $i++) {
            $block = '';
            for ($j = 0; $j < $blockSize; $j++) {
                $randomIndex = random_int(0, strlen(self::CHARSET) - 1);
                $block .= self::CHARSET[$randomIndex];
            }
            $parts[] = $block;
        }

        return $prefix . '-' . implode('-', $parts);
    }

    /**
     * Generate a unique license key guaranteed to be collision-free in the database
     */
    public static function generateUniqueKey(string $prefix = 'GB', int $maxAttempts = 20): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $key = self::generateKey($prefix);
            $exists = DatabaseConnection::fetchOne(
                "SELECT `id` FROM `licenses` WHERE `license_key` = ? LIMIT 1",
                [$key]
            );

            if (!$exists) {
                return $key;
            }
        }

        throw new Exception("Failed to generate a unique license key after {$maxAttempts} attempts.");
    }

    /**
     * Bulk generate unique collision-free license keys
     */
    public static function bulkGenerate(int $count, string $prefix = 'GB'): array
    {
        if ($count < 1 || $count > 10000) {
            throw new Exception("Bulk generation count must be between 1 and 10000.");
        }

        $keys = [];
        $keyMap = [];

        // Pre-fetch existing keys from DB to prevent collisions in high volume
        $existingKeysRows = DatabaseConnection::fetchAll("SELECT `license_key` FROM `licenses`");
        $existingMap = [];
        foreach ($existingKeysRows as $row) {
            $existingMap[$row['license_key']] = true;
        }

        while (count($keys) < $count) {
            $candidate = self::generateKey($prefix);
            if (!isset($existingMap[$candidate]) && !isset($keyMap[$candidate])) {
                $keyMap[$candidate] = true;
                $keys[] = $candidate;
            }
        }

        return $keys;
    }
}
