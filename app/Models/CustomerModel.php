<?php

namespace App\Models;

use App\Database\DatabaseConnection;
use App\Helpers\SecurityHelper;

class CustomerModel
{
    public static function findById(int $id): ?array
    {
        return DatabaseConnection::fetchOne("SELECT * FROM `customers` WHERE `id` = ?", [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DatabaseConnection::fetchOne("SELECT * FROM `customers` WHERE `email` = ?", [$email]);
    }

    /**
     * Find or create customer record by email
     */
    public static function findOrCreate(string $name, string $email, ?string $phone = null, ?string $company = null, ?string $country = null): int
    {
        $existing = self::findByEmail($email);
        if ($existing) {
            return (int) $existing['id'];
        }

        $uuid = SecurityHelper::generateUuid();
        DatabaseConnection::query("
            INSERT INTO `customers` (`uuid`, `name`, `email`, `phone`, `company`, `country`)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$uuid, $name, $email, $phone, $company, $country]);

        return (int) DatabaseConnection::lastInsertId();
    }
}
