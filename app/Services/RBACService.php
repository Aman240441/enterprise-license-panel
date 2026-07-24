<?php

namespace App\Services;

use App\Database\DatabaseConnection;

class RBACService
{
    /**
     * Check if user has specific permission slug
     */
    public static function hasPermission(int $userId, string $permissionSlug): bool
    {
        $user = DatabaseConnection::fetchOne("
            SELECT u.id, u.role_id, r.slug as role_slug 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE u.id = ? AND u.status = 'active'
        ", [$userId]);

        if (!$user) {
            return false;
        }

        // Super admin has unrestricted permission
        if ($user['role_slug'] === 'super_admin') {
            return true;
        }

        // Check per-user explicit permission overrides first
        $userPermission = DatabaseConnection::fetchOne("
            SELECT up.is_granted 
            FROM `user_permissions` up
            JOIN `permissions` p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.slug = ?
        ", [$userId, $permissionSlug]);

        if ($userPermission !== null) {
            return (bool) $userPermission['is_granted'];
        }

        // Check role permissions
        $rolePermission = DatabaseConnection::fetchOne("
            SELECT rp.role_id 
            FROM `role_permissions` rp
            JOIN `permissions` p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ", [$user['role_id'], $permissionSlug]);

        return $rolePermission !== null;
    }

    /**
     * Check if a reseller or employee is allowed to manage/generate keys for a specific product ID
     */
    public static function canAccessProduct(int $userId, int $productId): bool
    {
        $user = DatabaseConnection::fetchOne("
            SELECT u.id, r.slug as role_slug 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE u.id = ?
        ", [$userId]);

        if (!$user) return false;

        // Super Admin & Admin can access all products
        if (in_array($user['role_slug'], ['super_admin', 'admin'], true)) {
            return true;
        }

        // Check Reseller product assignments
        if ($user['role_slug'] === 'reseller') {
            $reseller = DatabaseConnection::fetchOne("SELECT allowed_products_json FROM `resellers` WHERE user_id = ?", [$userId]);
            if (!$reseller || empty($reseller['allowed_products_json'])) {
                return false;
            }
            $allowedList = json_decode($reseller['allowed_products_json'], true) ?? [];
            return in_array($productId, $allowedList, true);
        }

        return true;
    }

    /**
     * Check if user is Super Admin
     */
    public static function isSuperAdmin(int $userId): bool
    {
        $user = DatabaseConnection::fetchOne("
            SELECT r.slug FROM `users` u JOIN `roles` r ON u.role_id = r.id WHERE u.id = ?
        ", [$userId]);
        return $user && $user['slug'] === 'super_admin';
    }

    /**
     * Check if user is Reseller
     */
    public static function isReseller(int $userId): bool
    {
        $user = DatabaseConnection::fetchOne("
            SELECT r.slug FROM `users` u JOIN `roles` r ON u.role_id = r.id WHERE u.id = ?
        ", [$userId]);
        return $user && $user['slug'] === 'reseller';
    }

    /**
     * Verify employee/reseller generation quota against daily and monthly limits
     */
    public static function checkEmployeeGenerationQuota(int $userId): array
    {
        $user = DatabaseConnection::fetchOne("
            SELECT daily_gen_limit, monthly_gen_limit FROM `users` WHERE id = ?
        ", [$userId]);

        if (!$user) {
            return ['allowed' => false, 'message' => 'User account invalid'];
        }

        $dailyLimit = (int) $user['daily_gen_limit'];
        $monthlyLimit = (int) $user['monthly_gen_limit'];

        if ($dailyLimit === 0 && $monthlyLimit === 0) {
            return ['allowed' => true];
        }

        if ($dailyLimit > 0) {
            $todayCount = DatabaseConnection::fetchOne("
                SELECT COUNT(*) as cnt FROM `licenses` 
                WHERE created_by = ? AND DATE(created_at) = CURDATE()
            ", [$userId])['cnt'] ?? 0;

            if ($todayCount >= $dailyLimit) {
                return ['allowed' => false, 'message' => "Daily generation limit reached ({$dailyLimit} keys/day)."];
            }
        }

        if ($monthlyLimit > 0) {
            $monthCount = DatabaseConnection::fetchOne("
                SELECT COUNT(*) as cnt FROM `licenses` 
                WHERE created_by = ? AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
            ", [$userId])['cnt'] ?? 0;

            if ($monthCount >= $monthlyLimit) {
                return ['allowed' => false, 'message' => "Monthly generation limit reached ({$monthlyLimit} keys/month)."];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Get array of all assigned permission slugs for a user
     */
    public static function getUserPermissions(int $userId): array
    {
        $user = DatabaseConnection::fetchOne("
            SELECT u.id, u.role_id, r.slug as role_slug 
            FROM `users` u 
            JOIN `roles` r ON u.role_id = r.id 
            WHERE u.id = ? AND u.status = 'active'
        ", [$userId]);

        if (!$user) {
            return [];
        }

        // Super Admin has all system permissions
        if ($user['role_slug'] === 'super_admin') {
            $all = DatabaseConnection::fetchAll("SELECT `slug` FROM `permissions`");
            return array_column($all, 'slug');
        }

        // Fetch permissions tied to user's role
        $rolePerms = DatabaseConnection::fetchAll("
            SELECT p.slug 
            FROM `role_permissions` rp
            JOIN `permissions` p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ", [$user['role_id']]);

        $perms = array_column($rolePerms, 'slug');

        // Apply user-specific explicit overrides (granted or revoked)
        $userPerms = DatabaseConnection::fetchAll("
            SELECT p.slug, up.is_granted
            FROM `user_permissions` up
            JOIN `permissions` p ON up.permission_id = p.id
            WHERE up.user_id = ?
        ", [$userId]);

        foreach ($userPerms as $up) {
            if ((int)$up['is_granted'] === 1) {
                if (!in_array($up['slug'], $perms, true)) {
                    $perms[] = $up['slug'];
                }
            } else {
                $perms = array_values(array_filter($perms, fn($p) => $p !== $up['slug']));
            }
        }

        return $perms;
    }
}
