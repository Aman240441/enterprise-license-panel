<?php

namespace App\Models;

use App\Database\DatabaseConnection;

class PlanModel
{
    public static function findById(int $id): ?array
    {
        return DatabaseConnection::fetchOne("SELECT * FROM `license_plans` WHERE `id` = ?", [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DatabaseConnection::fetchOne("SELECT * FROM `license_plans` WHERE `slug` = ?", [$slug]);
    }

    public static function getActivePlansForProduct(?int $productId = null): array
    {
        if ($productId !== null) {
            return DatabaseConnection::fetchAll("
                SELECT * FROM `license_plans` 
                WHERE `is_active` = 1 AND (`product_id` = ? OR `product_id` IS NULL)
                ORDER BY `price` ASC
            ", [$productId]);
        }
        return DatabaseConnection::fetchAll("SELECT * FROM `license_plans` WHERE `is_active` = 1 ORDER BY `price` ASC");
    }
}
