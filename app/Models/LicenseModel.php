<?php

namespace App\Models;

use App\Database\DatabaseConnection;

class LicenseModel
{
    public static function findById(int $id): ?array
    {
        return DatabaseConnection::fetchOne("
            SELECT l.*, p.name as product_name, p.slug as product_slug, lp.name as plan_name, lp.slug as plan_slug,
                   c.name as customer_name, c.email as customer_email, c.company as customer_company, c.country as customer_country,
                   u.full_name as creator_name, u.email as creator_email
            FROM `licenses` l
            JOIN `products` p ON l.product_id = p.id
            JOIN `license_plans` lp ON l.plan_id = lp.id
            LEFT JOIN `customers` c ON l.customer_id = c.id
            JOIN `users` u ON l.created_by = u.id
            WHERE l.id = ?
        ", [$id]);
    }

    public static function findByKey(string $key): ?array
    {
        $cleanKey = strtoupper(trim($key));
        return DatabaseConnection::fetchOne("
            SELECT l.*, p.name as product_name, p.slug as product_slug, p.secret_key as product_secret, p.status as product_status,
                   lp.name as plan_name, lp.slug as plan_slug
            FROM `licenses` l
            JOIN `products` p ON l.product_id = p.id
            JOIN `license_plans` lp ON l.plan_id = lp.id
            WHERE UPPER(TRIM(l.license_key)) = ?
        ", [$cleanKey]);
    }

    /**
     * Get Summary Metrics Counts for Dashboard Cards
     */
    public static function getSummaryCounts(?array $currentUser = null): array
    {
        $where = ["1=1"];
        $params = [];

        if ($currentUser !== null && $currentUser['role_slug'] === 'employee') {
            $where[] = "l.created_by = ?";
            $params[] = $currentUser['id'];
        }

        if ($currentUser !== null && $currentUser['role_slug'] === 'reseller') {
            $reseller = DatabaseConnection::fetchOne("SELECT id FROM `resellers` WHERE user_id = ?", [$currentUser['id']]);
            if ($reseller) {
                $where[] = "l.reseller_id = ?";
                $params[] = $reseller['id'];
            }
        }

        $whereSql = implode(" AND ", $where);

        $counts = DatabaseConnection::fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN l.status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN l.status = 'expired' THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN l.status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                SUM(CASE WHEN l.status = 'revoked' THEN 1 ELSE 0 END) as revoked,
                SUM(CASE WHEN l.status = 'draft' THEN 1 ELSE 0 END) as draft
            FROM `licenses` l
            WHERE {$whereSql}
        ", $params);

        return [
            'total'     => (int) ($counts['total'] ?? 0),
            'active'    => (int) ($counts['active'] ?? 0),
            'inactive'  => (int) ($counts['inactive'] ?? 0),
            'expired'   => (int) ($counts['expired'] ?? 0),
            'suspended' => (int) ($counts['suspended'] ?? 0),
            'revoked'   => (int) ($counts['revoked'] ?? 0),
            'draft'     => (int) ($counts['draft'] ?? 0)
        ];
    }

    /**
     * Search and filter licenses with pagination, date range, and RBAC user scoping
     */
    public static function search(array $filters = [], int $page = 1, int $perPage = 25, ?array $currentUser = null): array
    {
        $where = ["1=1"];
        $params = [];

        // Scoping for Employees (can only see licenses created by themselves)
        if ($currentUser !== null && $currentUser['role_slug'] === 'employee') {
            $where[] = "l.created_by = ?";
            $params[] = $currentUser['id'];
        }

        // Scoping for Resellers
        if ($currentUser !== null && $currentUser['role_slug'] === 'reseller') {
            $reseller = DatabaseConnection::fetchOne("SELECT id FROM `resellers` WHERE user_id = ?", [$currentUser['id']]);
            if ($reseller) {
                $where[] = "l.reseller_id = ?";
                $params[] = $reseller['id'];
            }
        }

        // Product Filter
        if (!empty($filters['product_id'])) {
            $where[] = "l.product_id = ?";
            $params[] = $filters['product_id'];
        }

        // Search Query (License Key, Customer Name, Email, Company, Phone)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $where[] = "(l.license_key LIKE ? OR c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.phone LIKE ?)";
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }

        // Status Filter
        if (!empty($filters['status'])) {
            $where[] = "l.status = ?";
            $params[] = $filters['status'];
        }

        // Plan Filter
        if (!empty($filters['plan_id'])) {
            $where[] = "l.plan_id = ?";
            $params[] = $filters['plan_id'];
        }

        // Country Filter
        if (!empty($filters['country'])) {
            $where[] = "c.country = ?";
            $params[] = $filters['country'];
        }

        // Date Range Filters (created_at or expiry_date)
        if (!empty($filters['date_from'])) {
            $where[] = "l.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "l.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = implode(" AND ", $where);

        // Count Total Matches
        $countRow = DatabaseConnection::fetchOne("
            SELECT COUNT(*) as total 
            FROM `licenses` l
            LEFT JOIN `customers` c ON l.customer_id = c.id
            WHERE {$whereSql}
        ", $params);
        $totalRows = (int) ($countRow['total'] ?? 0);

        // Pagination
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT l.*, p.name as product_name, lp.name as plan_name,
                   c.name as customer_name, c.email as customer_email, c.company as customer_company, c.country as customer_country, c.phone as customer_phone,
                   u.full_name as creator_name
            FROM `licenses` l
            JOIN `products` p ON l.product_id = p.id
            JOIN `license_plans` lp ON l.plan_id = lp.id
            LEFT JOIN `customers` c ON l.customer_id = c.id
            JOIN `users` u ON l.created_by = u.id
            WHERE {$whereSql}
            ORDER BY l.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $records = DatabaseConnection::fetchAll($sql, $params);

        return [
            'total'        => $totalRows,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => (int) ceil($totalRows / $perPage),
            'items'        => $records
        ];
    }

    /**
     * Update license status (draft, active, inactive, expired, revoked, suspended)
     */
    public static function updateStatus(int $licenseId, string $status): bool
    {
        return DatabaseConnection::execute("
            UPDATE `licenses` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?
        ", [$status, $licenseId]) > 0;
    }

    /**
     * Update license details (customer, plan, product, status, allowed_devices, expiry_date, notes)
     */
    public static function updateLicense(int $licenseId, array $data): bool
    {
        $fields = [];
        $params = [];

        if (array_key_exists('product_id', $data)) {
            $fields[] = "`product_id` = ?";
            $params[] = $data['product_id'];
        }
        if (array_key_exists('plan_id', $data)) {
            $fields[] = "`plan_id` = ?";
            $params[] = $data['plan_id'];
        }
        if (array_key_exists('status', $data)) {
            $fields[] = "`status` = ?";
            $params[] = $data['status'];
        }
        if (array_key_exists('allowed_devices', $data)) {
            $fields[] = "`allowed_devices` = ?";
            $params[] = $data['allowed_devices'];
        }
        if (array_key_exists('expiry_date', $data)) {
            $fields[] = "`expiry_date` = ?";
            $params[] = $data['expiry_date'];
        }
        if (array_key_exists('customer_id', $data)) {
            $fields[] = "`customer_id` = ?";
            $params[] = $data['customer_id'];
        }
        if (array_key_exists('notes', $data)) {
            $fields[] = "`notes` = ?";
            $params[] = $data['notes'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "`updated_at` = NOW()";
        $params[] = $licenseId;

        $setSql = implode(", ", $fields);
        return DatabaseConnection::execute("UPDATE `licenses` SET {$setSql} WHERE `id` = ?", $params) > 0;
    }

    /**
     * Extend License Expiry Date
     */
    public static function extendExpiry(int $licenseId, string $newExpiry): bool
    {
        return DatabaseConnection::execute("
            UPDATE `licenses` SET `expiry_date` = ?, `updated_at` = NOW() WHERE `id` = ?
        ", [$newExpiry, $licenseId]) > 0;
    }

    /**
     * Reset Connected Devices for a license
     */
    public static function resetDevices(int $licenseId): bool
    {
        DatabaseConnection::beginTransaction();
        try {
            // Deactivate device records
            DatabaseConnection::execute("UPDATE `devices` SET `is_active` = 0 WHERE `license_id` = ?", [$licenseId]);
            // Reset current devices count
            DatabaseConnection::execute("UPDATE `licenses` SET `current_devices` = 0, `updated_at` = NOW() WHERE `id` = ?", [$licenseId]);
            DatabaseConnection::commit();
            return true;
        } catch (\Exception $e) {
            DatabaseConnection::rollBack();
            return false;
        }
    }

    /**
     * Bulk update status for an array of license IDs
     */
    public static function bulkUpdateStatus(array $licenseIds, string $status): int
    {
        if (empty($licenseIds)) return 0;
        $inQuery = implode(',', array_fill(0, count($licenseIds), '?'));
        $params = array_merge([$status], $licenseIds);
        return DatabaseConnection::execute("UPDATE `licenses` SET `status` = ?, `updated_at` = NOW() WHERE `id` IN ({$inQuery})", $params);
    }

    /**
     * Bulk delete an array of license IDs
     */
    public static function bulkDelete(array $licenseIds): int
    {
        if (empty($licenseIds)) return 0;
        $inQuery = implode(',', array_fill(0, count($licenseIds), '?'));
        return DatabaseConnection::execute("DELETE FROM `licenses` WHERE `id` IN ({$inQuery})", $licenseIds);
    }

    /**
     * Fetch Audit History Logs for a License
     */
    public static function getAuditHistory(int $licenseId): array
    {
        return DatabaseConnection::fetchAll("
            SELECT a.*, u.full_name as user_name, u.email as user_email
            FROM `activity_logs` a
            LEFT JOIN `users` u ON a.user_id = u.id
            WHERE a.entity_type = 'licenses' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT 50
        ", [$licenseId]);
    }
}
