<?php

namespace App\Services\Storage;

class StorageManager
{
    private static ?StorageDriverInterface $driver = null;

    /**
     * Get active Storage Driver instance based on configuration
     */
    public static function getDriver(?string $driverName = null): StorageDriverInterface
    {
        if (self::$driver !== null && $driverName === null) {
            return self::$driver;
        }

        $selected = strtolower($driverName ?? $_ENV['STORAGE_DRIVER'] ?? 'local');

        self::$driver = match ($selected) {
            'supabase' => new SupabaseStorageDriver(),
            's3' => new S3StorageDriver(),
            'r2' => new R2StorageDriver(),
            default => new LocalStorageDriver(),
        };

        return self::$driver;
    }
}
