<?php

namespace App\Models;

use App\Database\DatabaseConnection;
use App\Helpers\SecurityHelper;

class UserModel
{
    public static function findById(int $id): ?array
    {
        return DatabaseConnection::fetchOne("
            SELECT u.*, r.slug as role_slug, r.name as role_name 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE u.id = ? AND u.deleted_at IS NULL
        ", [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DatabaseConnection::fetchOne("
            SELECT u.*, r.slug as role_slug, r.name as role_name 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE u.email = ? AND u.deleted_at IS NULL
        ", [strtolower(trim($email))]);
    }

    public static function search(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ["u.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['role_slug'])) {
            $where[] = "r.slug = ?";
            $params[] = $filters['role_slug'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
            array_push($params, $term, $term);
        }

        $whereSql = implode(" AND ", $where);
        $offset = ($page - 1) * $perPage;

        $countRow = DatabaseConnection::fetchOne("
            SELECT COUNT(*) as total 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE {$whereSql}
        ", $params);
        $total = (int) ($countRow['total'] ?? 0);

        $users = DatabaseConnection::fetchAll("
            SELECT u.id, u.uuid, u.full_name, u.email, u.status, u.daily_gen_limit, u.monthly_gen_limit,
                   u.last_login_at, u.created_at, r.slug as role_slug, r.name as role_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE {$whereSql}
            ORDER BY u.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        return [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage),
            'items'       => $users
        ];
    }
}
