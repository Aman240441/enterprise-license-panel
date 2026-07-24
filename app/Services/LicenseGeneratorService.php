<?php

namespace App\Services;

use App\Database\DatabaseConnection;
use App\Helpers\KeyGenerator;
use App\Helpers\SecurityHelper;
use App\Models\CustomerModel;
use App\Models\PlanModel;
use Exception;

class LicenseGeneratorService
{
    /**
     * Generate Single License Key
     */
    public static function generateSingle(array $params, array $currentUser): array
    {
        $userId = $currentUser['id'];

        // 1. Permission check
        if (!RBACService::hasPermission($userId, 'licenses.generate')) {
            throw new Exception("Access Denied: You do not have permission to generate licenses.");
        }

        // 2. Generation Quota Check
        $quotaCheck = RBACService::checkEmployeeGenerationQuota($userId);
        if (!$quotaCheck['allowed']) {
            throw new Exception($quotaCheck['message']);
        }

        // 3. Product Validation & Product Scoping Check
        $productId = (int) ($params['product_id'] ?? 0);
        $product = DatabaseConnection::fetchOne("SELECT * FROM `products` WHERE id = ? AND status = 'active'", [$productId]);
        if (!$product) {
            throw new Exception("Selected software product is invalid or inactive.");
        }

        if (!RBACService::canAccessProduct($userId, $productId)) {
            throw new Exception("Access Denied: You are not authorized to generate licenses for this product.");
        }

        // 4. Plan Validation
        $planId = (int) ($params['plan_id'] ?? 0);
        $plan = PlanModel::findById($planId);
        if (!$plan || (int)$plan['is_active'] !== 1) {
            throw new Exception("Selected license plan is invalid or inactive.");
        }

        // 5. Expiry Date Calculation
        $expiryDate = self::calculateExpiryDate($plan);
        $maxDevices = isset($params['allowed_devices']) && $params['allowed_devices'] !== '' ? (int)$params['allowed_devices'] : 1;
        $uploadPermission = isset($params['upload_permission']) ? (int)$params['upload_permission'] : 1;
        $prefix = 'GB';

        // 6. Customer Auto Creation / Linking
        $customerId = null;
        if (!empty($params['customer_email']) && !empty($params['customer_name'])) {
            $customerId = CustomerModel::findOrCreate(
                trim($params['customer_name']),
                strtolower(trim($params['customer_email'])),
                $params['customer_phone'] ?? null,
                $params['customer_company'] ?? null,
                $params['customer_country'] ?? null
            );
        }

        // 7. Reseller Linking & Commission Calculation
        $resellerId = null;
        if ($currentUser['role_slug'] === 'reseller') {
            $reseller = DatabaseConnection::fetchOne("SELECT id, commission_rate FROM `resellers` WHERE user_id = ?", [$userId]);
            if ($reseller) {
                $resellerId = (int) $reseller['id'];
                $planPrice = (float) $plan['price'];
                $earnings = $planPrice * (((float)$reseller['commission_rate']) / 100);

                DatabaseConnection::query(
                    "UPDATE `resellers` SET total_sales = total_sales + ?, total_earnings = total_earnings + ? WHERE id = ?",
                    [$planPrice, $earnings, $resellerId]
                );
            }
        }

        // 8. Generate Collision-Free Key
        $licenseKey = KeyGenerator::generateUniqueKey($prefix);
        $uuid = SecurityHelper::generateUuid();

        DatabaseConnection::query("
            INSERT INTO `licenses` 
            (`uuid`, `product_id`, `license_key`, `customer_id`, `plan_id`, `created_by`, `reseller_id`, `license_type`, `status`, `expiry_date`, `allowed_devices`, `upload_permission`, `notes`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?)
        ", [
            $uuid,
            $productId,
            $licenseKey,
            $customerId,
            $plan['id'],
            $userId,
            $resellerId,
            $plan['duration_type'],
            $expiryDate,
            $maxDevices,
            $uploadPermission,
            $params['notes'] ?? null
        ]);

        $licenseId = (int) DatabaseConnection::lastInsertId();

        // 9. Audit Logging
        AuditLoggerService::log(
            $userId,
            'license.generate_single',
            "Generated single license key {$licenseKey} for product {$product['name']}",
            'licenses',
            $licenseId,
            ['license_key' => $licenseKey, 'product' => $product['name'], 'plan' => $plan['name']],
            201,
            $currentUser['role_slug']
        );

        return [
            'id'             => $licenseId,
            'uuid'           => $uuid,
            'product_name'   => $product['name'],
            'license_key'    => $licenseKey,
            'plan_name'      => $plan['name'],
            'license_type'   => $plan['duration_type'],
            'expiry_date'    => $expiryDate,
            'allowed_devices'=> $maxDevices,
            'status'         => 'active',
            'created_at'     => date('c')
        ];
    }

    /**
     * Bulk Generate License Keys (100 to 10,000) using High-Performance Transaction
     */
    public static function bulkGenerate(array $params, array $currentUser): array
    {
        $userId = $currentUser['id'];

        if (!RBACService::hasPermission($userId, 'licenses.bulk_generate')) {
            throw new Exception("Access Denied: You do not have permission to bulk generate licenses.");
        }

        $count = (int) ($params['count'] ?? 100);
        if ($count < 1 || $count > 10000) {
            throw new Exception("Bulk generation count must be between 1 and 10,000.");
        }

        $quotaCheck = RBACService::checkEmployeeGenerationQuota($userId);
        if (!$quotaCheck['allowed']) {
            throw new Exception($quotaCheck['message']);
        }

        $productId = (int) ($params['product_id'] ?? 0);
        $product = DatabaseConnection::fetchOne("SELECT * FROM `products` WHERE id = ? AND status = 'active'", [$productId]);
        if (!$product) {
            throw new Exception("Selected software product is invalid or inactive.");
        }

        if (!RBACService::canAccessProduct($userId, $productId)) {
            throw new Exception("Access Denied: You are not authorized for this product.");
        }

        $planId = (int) ($params['plan_id'] ?? 0);
        $plan = PlanModel::findById($planId);
        if (!$plan || (int)$plan['is_active'] !== 1) {
            throw new Exception("Selected license plan is invalid or inactive.");
        }

        $expiryDate = self::calculateExpiryDate($plan);
        $maxDevices = isset($params['allowed_devices']) && $params['allowed_devices'] !== '' ? (int)$params['allowed_devices'] : 1;
        $prefix = 'GB';

        // Generate unique keys array
        $keys = KeyGenerator::bulkGenerate($count, $prefix);

        $resellerId = null;
        if ($currentUser['role_slug'] === 'reseller') {
            $reseller = DatabaseConnection::fetchOne("SELECT id, commission_rate FROM `resellers` WHERE user_id = ?", [$userId]);
            if ($reseller) {
                $resellerId = (int) $reseller['id'];
                $totalPrice = ((float)$plan['price']) * $count;
                $earnings = $totalPrice * (((float)$reseller['commission_rate']) / 100);

                DatabaseConnection::query(
                    "UPDATE `resellers` SET total_sales = total_sales + ?, total_earnings = total_earnings + ? WHERE id = ?",
                    [$totalPrice, $earnings, $resellerId]
                );
            }
        }

        DatabaseConnection::beginTransaction();
        try {
            $sql = "INSERT INTO `licenses` (`uuid`, `product_id`, `license_key`, `plan_id`, `created_by`, `reseller_id`, `license_type`, `status`, `expiry_date`, `allowed_devices`, `notes`) VALUES ";
            $valueRows = [];
            $queryParams = [];

            foreach ($keys as $key) {
                $uuid = SecurityHelper::generateUuid();
                $valueRows[] = "(?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)";
                array_push(
                    $queryParams,
                    $uuid,
                    $productId,
                    $key,
                    $plan['id'],
                    $userId,
                    $resellerId,
                    $plan['duration_type'],
                    $expiryDate,
                    $maxDevices,
                    $params['notes'] ?? 'Bulk Generated'
                );
            }

            $sql .= implode(", ", $valueRows);
            DatabaseConnection::query($sql, $queryParams);
            DatabaseConnection::commit();
        } catch (Exception $e) {
            DatabaseConnection::rollBack();
            throw new Exception("Bulk generation database transaction failed: " . $e->getMessage());
        }

        AuditLoggerService::log(
            $userId,
            'license.bulk_generate',
            "Bulk generated {$count} license keys for product {$product['name']}",
            'licenses',
            null,
            ['count' => $count, 'product' => $product['name'], 'plan' => $plan['name']],
            201,
            $currentUser['role_slug']
        );

        return [
            'count'        => $count,
            'product_name' => $product['name'],
            'plan_name'    => $plan['name'],
            'license_type' => $plan['duration_type'],
            'expiry_date'  => $expiryDate,
            'keys'         => $keys
        ];
    }

    /**
     * Calculate Expiry Date from Plan Rules:
     * - Standard Plans: Exact calendar math (+7 days, +1 month, +X months, +12 months)
     * - Lifetime: 31-12-2099
     * - Business & Partner Plans: Uses configured duration_days from Admin settings
     */
    private static function calculateExpiryDate(array $plan): string
    {
        $slug = $plan['slug'] ?? '';
        $type = $plan['duration_type'] ?? '';
        $days = (int) ($plan['duration_days'] ?? 30);

        if ($slug === 'lifetime' || $type === 'lifetime') {
            return '2099-12-31 23:59:59';
        }

        // Standard Plans
        if ($slug === 'free_trial' || $type === 'trial') return date('Y-m-d H:i:s', strtotime('+7 days'));
        if ($slug === 'monthly'    || $type === '1_month') return date('Y-m-d H:i:s', strtotime('+1 month'));
        if ($slug === '2_months'   || $type === '2_months') return date('Y-m-d H:i:s', strtotime('+2 months'));
        if ($slug === '3_months'   || $type === '3_months') return date('Y-m-d H:i:s', strtotime('+3 months'));
        if ($slug === '4_months'   || $type === '4_months') return date('Y-m-d H:i:s', strtotime('+4 months'));
        if ($slug === '6_months'   || $type === '6_months') return date('Y-m-d H:i:s', strtotime('+6 months'));
        if ($slug === '7_months'   || $type === '7_months') return date('Y-m-d H:i:s', strtotime('+7 months'));
        if ($slug === '8_months'   || $type === '8_months') return date('Y-m-d H:i:s', strtotime('+8 months'));
        if ($slug === '9_months'   || $type === '9_months') return date('Y-m-d H:i:s', strtotime('+9 months'));
        if ($slug === '12_months'  || $type === '12_months') return date('Y-m-d H:i:s', strtotime('+12 months'));

        // Business & Partner Plans
        if ($days > 0) {
            return date('Y-m-d H:i:s', strtotime("+{$days} days"));
        }

        return date('Y-m-d H:i:s', strtotime('+30 days'));
    }
}
