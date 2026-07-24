<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RBACMiddleware;
use App\Models\LicenseModel;
use App\Services\AuditLoggerService;
use App\Services\LicenseGeneratorService;
use App\Services\RBACService;
use Exception;

class LicenseApiController
{
    /**
     * POST /api/v1/licenses/generate
     * Generate single license key
     */
    public function generate(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $body = $request->getBody();

        $validator = Validator::make($body, [
            'product_id' => 'required|integer',
            'plan_id'    => 'required|integer'
        ]);

        if ($validator->fails()) {
            ResponseHelper::error("Validation failed", 422, $validator->errors());
        }

        try {
            $result = LicenseGeneratorService::generateSingle($body, $currentUser);
            ResponseHelper::created($result, "License key generated successfully");
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/licenses/bulk-generate
     * Bulk generate 100 - 10,000 license keys
     */
    public function bulkGenerate(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $body = $request->getBody();

        $validator = Validator::make($body, [
            'product_id' => 'required|integer',
            'plan_id'    => 'required|integer',
            'count'      => 'required|integer'
        ]);

        if ($validator->fails()) {
            ResponseHelper::error("Validation failed", 422, $validator->errors());
        }

        try {
            $result = LicenseGeneratorService::bulkGenerate($body, $currentUser);
            ResponseHelper::created($result, "Bulk license keys generated successfully");
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/v1/licenses
     * Filterable & Searchable License Registry
     */
    public function list(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $params = $request->getParams();

        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 25);

        $result = LicenseModel::search($params, $page, $perPage, $currentUser);
        ResponseHelper::success($result['items'], "Licenses retrieved", 200, [
            'total'       => $result['total'],
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total_pages' => $result['total_pages']
        ]);
    }

    /**
     * GET /api/v1/licenses/{id}
     * Retrieve details of a single license key along with activated devices
     */
    public function show(Request $request, array $routeParams): void
    {
        $currentUser = AuthMiddleware::handle();
        $licenseId = (int) ($routeParams['id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License key not found.");
        }

        // Check user permission scoping
        if ($currentUser['role_slug'] === 'employee' && (int)$license['created_by'] !== $currentUser['id']) {
            ResponseHelper::forbidden("Access Denied: You can only view licenses created by yourself.");
        }

        // Fetch activated devices for this license
        $devices = DatabaseConnection::fetchAll("
            SELECT * FROM `devices` WHERE license_id = ? ORDER BY last_seen DESC
        ", [$licenseId]);

        $license['devices'] = $devices;

        ResponseHelper::success($license, "License details retrieved");
    }

    /**
     * GET /api/v1/licenses/summary
     * Retrieve summary counts for dashboard metric cards
     */
    public function summary(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $counts = LicenseModel::getSummaryCounts($currentUser);
        ResponseHelper::success($counts, "License summary retrieved");
    }

    /**
     * GET /api/v1/licenses/{id}/audit
     * Fetch audit history log for a specific license
     */
    public function auditHistory(Request $request, array $routeParams): void
    {
        $currentUser = AuthMiddleware::handle();
        $licenseId = (int) ($routeParams['id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        $logs = LicenseModel::getAuditHistory($licenseId);
        ResponseHelper::success($logs, "License audit history retrieved");
    }

    /**
     * POST /api/v1/licenses/update
     * Update license details (customer details, product, plan, status, allowed devices, expiry date, notes)
     */
    public function update(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.edit');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        $oldStatus = $license['status'];
        $oldAllowed = $license['allowed_devices'];
        $oldExpiry = $license['expiry_date'];

        // Customer handling if name & email provided
        $customerId = $license['customer_id'];
        if (!empty($body['customer_name']) && !empty($body['customer_email'])) {
            $customerId = \App\Models\CustomerModel::findOrCreate(
                trim($body['customer_name']),
                strtolower(trim($body['customer_email'])),
                $body['customer_phone'] ?? null,
                $body['customer_company'] ?? null,
                $body['customer_country'] ?? null
            );
        }

        $updateData = [];
        if (isset($body['product_id'])) $updateData['product_id'] = (int)$body['product_id'];
        if (isset($body['plan_id'])) $updateData['plan_id'] = (int)$body['plan_id'];
        if (isset($body['status'])) $updateData['status'] = trim($body['status']);
        if (isset($body['allowed_devices'])) $updateData['allowed_devices'] = (int)$body['allowed_devices'];
        if (isset($body['expiry_date'])) $updateData['expiry_date'] = !empty($body['expiry_date']) ? trim($body['expiry_date']) : null;
        if (isset($body['notes'])) $updateData['notes'] = trim($body['notes']);
        if ($customerId !== null) $updateData['customer_id'] = $customerId;

        LicenseModel::updateLicense($licenseId, $updateData);

        AuditLoggerService::log(
            $currentUser['id'],
            'license.edit',
            "Updated license key {$license['license_key']}. Changes: " . json_encode($updateData),
            'licenses',
            $licenseId,
            [
                'old_status' => $oldStatus,
                'new_status' => $updateData['status'] ?? $oldStatus,
                'old_allowed_devices' => $oldAllowed,
                'new_allowed_devices' => $updateData['allowed_devices'] ?? $oldAllowed,
                'old_expiry' => $oldExpiry,
                'new_expiry' => $updateData['expiry_date'] ?? $oldExpiry
            ],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License updated successfully.");
    }

    /**
     * POST /api/v1/licenses/activate
     * Activate a license key
     */
    public function activate(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::updateStatus($licenseId, 'active');

        AuditLoggerService::log(
            $currentUser['id'],
            'license.activate',
            "Activated license key {$license['license_key']}",
            'licenses',
            $licenseId,
            ['old_status' => $license['status'], 'new_status' => 'active'],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License activated successfully.");
    }

    /**
     * POST /api/v1/licenses/deactivate
     * Deactivate a license key
     */
    public function deactivate(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::updateStatus($licenseId, 'inactive');

        AuditLoggerService::log(
            $currentUser['id'],
            'license.deactivate',
            "Deactivated license key {$license['license_key']}",
            'licenses',
            $licenseId,
            ['old_status' => $license['status'], 'new_status' => 'inactive'],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License deactivated successfully.");
    }

    /**
     * POST /api/v1/licenses/extend-expiry
     * Extend expiry date of a license key
     */
    public function extendExpiry(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.edit');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);
        $newExpiry = trim($body['expiry_date'] ?? '');

        if (empty($newExpiry)) {
            ResponseHelper::error("New expiry date is required", 422);
        }

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::extendExpiry($licenseId, $newExpiry);

        AuditLoggerService::log(
            $currentUser['id'],
            'license.extend_expiry',
            "Extended expiry date for license {$license['license_key']} from {$license['expiry_date']} to {$newExpiry}",
            'licenses',
            $licenseId,
            ['old_expiry' => $license['expiry_date'], 'new_expiry' => $newExpiry],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License expiry date extended successfully.");
    }

    /**
     * POST /api/v1/licenses/reset-devices
     * Reset connected devices for a license key
     */
    public function resetDevices(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.edit');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::resetDevices($licenseId);

        AuditLoggerService::log(
            $currentUser['id'],
            'license.reset_devices',
            "Reset connected devices for license key {$license['license_key']}",
            'licenses',
            $licenseId,
            ['old_devices_count' => $license['current_devices'], 'new_devices_count' => 0],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "Connected devices reset successfully.");
    }

    /**
     * POST /api/v1/licenses/bulk-action
     * Handle bulk actions: activate, deactivate, suspend, delete, export
     */
    public function bulkAction(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $body = $request->getBody();

        $action = trim($body['action'] ?? '');
        $licenseIds = $body['license_ids'] ?? [];

        if (empty($action) || !is_array($licenseIds) || empty($licenseIds)) {
            ResponseHelper::error("Action and license_ids array are required.", 422);
        }

        $ids = array_map('intval', $licenseIds);
        $count = count($ids);

        switch ($action) {
            case 'activate':
                LicenseModel::bulkUpdateStatus($ids, 'active');
                AuditLoggerService::log($currentUser['id'], 'license.bulk_activate', "Bulk activated {$count} licenses", 'licenses', null, ['ids' => $ids], 200, $currentUser['role_slug']);
                ResponseHelper::success(null, "Successfully activated {$count} licenses.");
                break;

            case 'deactivate':
                LicenseModel::bulkUpdateStatus($ids, 'inactive');
                AuditLoggerService::log($currentUser['id'], 'license.bulk_deactivate', "Bulk deactivated {$count} licenses", 'licenses', null, ['ids' => $ids], 200, $currentUser['role_slug']);
                ResponseHelper::success(null, "Successfully deactivated {$count} licenses.");
                break;

            case 'suspend':
                LicenseModel::bulkUpdateStatus($ids, 'suspended');
                AuditLoggerService::log($currentUser['id'], 'license.bulk_suspend', "Bulk suspended {$count} licenses", 'licenses', null, ['ids' => $ids], 200, $currentUser['role_slug']);
                ResponseHelper::success(null, "Successfully suspended {$count} licenses.");
                break;

            case 'delete':
                RBACMiddleware::requirePermission('licenses.delete');
                LicenseModel::bulkDelete($ids);
                AuditLoggerService::log($currentUser['id'], 'license.bulk_delete', "Bulk deleted {$count} licenses", 'licenses', null, ['ids' => $ids], 200, $currentUser['role_slug']);
                ResponseHelper::success(null, "Successfully deleted {$count} licenses.");
                break;

            default:
                ResponseHelper::error("Unsupported bulk action.", 400);
        }
    }

    /**
     * POST /api/v1/licenses/revoke
     * Revoke an active license
     */
    public function revoke(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.revoke');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::updateStatus($licenseId, 'revoked');

        AuditLoggerService::log(
            $currentUser['id'],
            'license.revoke',
            "Revoked license key {$license['license_key']}",
            'licenses',
            $licenseId,
            ['old_status' => $license['status'], 'new_status' => 'revoked'],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License revoked successfully.");
    }

    /**
     * POST /api/v1/licenses/suspend
     * Suspend an active license
     */
    public function suspend(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.suspend');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        LicenseModel::updateStatus($licenseId, 'suspended');

        AuditLoggerService::log(
            $currentUser['id'],
            'license.suspend',
            "Suspended license key {$license['license_key']}",
            'licenses',
            $licenseId,
            ['old_status' => $license['status'], 'new_status' => 'suspended'],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License suspended successfully.");
    }

    /**
     * DELETE /api/v1/licenses/delete
     * Delete a license record
     */
    public function delete(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.delete');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License not found.");
        }

        DatabaseConnection::query("DELETE FROM `licenses` WHERE id = ?", [$licenseId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'license.delete',
            "Deleted license key {$license['license_key']}",
            'licenses',
            $licenseId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "License deleted successfully.");
    }

    /**
     * GET/POST /api/v1/licenses/export
     * Export licenses to CSV
     */
    public function export(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $params = $request->getParams();

        $result = LicenseModel::search($params, 1, 10000, $currentUser);
        $licenses = $result['items'];

        if (empty($licenses)) {
            ResponseHelper::success([], "No licenses found to export.");
            return;
        }

        $csvRows = [];
        $csvRows[] = ['ID', 'License Key', 'Product', 'Plan', 'Status', 'Customer Name', 'Customer Email', 'Company', 'Allowed Devices', 'Current Devices', 'Expiry Date', 'Created Date'];

        foreach ($licenses as $lic) {
            $csvRows[] = [
                $lic['id'],
                $lic['license_key'],
                $lic['product_name'] ?? '',
                $lic['plan_name'] ?? '',
                $lic['status'],
                $lic['customer_name'] ?? '',
                $lic['customer_email'] ?? '',
                $lic['customer_company'] ?? '',
                $lic['allowed_devices'],
                $lic['current_devices'],
                $lic['expiry_date'] ?? 'Lifetime',
                $lic['created_at']
            ];
        }

        AuditLoggerService::log(
            $currentUser['id'],
            'license.export',
            "Exported " . count($licenses) . " licenses to CSV",
            'licenses',
            null,
            ['count' => count($licenses)],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success([
            'total_exported' => count($licenses),
            'columns'        => $csvRows[0],
            'rows'           => array_slice($csvRows, 1)
        ], "License export generated successfully");
    }

    /**
     * POST /api/v1/licenses/import
     * Bulk import licenses from CSV data
     */
    public function import(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('licenses.create');

        $body = $request->getBody();
        $rows = $body['rows'] ?? [];

        if (empty($rows) || !is_array($rows)) {
            ResponseHelper::error("CSV 'rows' array is required for import.", 400);
        }

        $db = DatabaseConnection::getInstance();
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $productId = (int) ($row['product_id'] ?? 1);
                $planId    = (int) ($row['plan_id'] ?? 1);

                $licenseKey = !empty($row['license_key']) ? trim($row['license_key']) : \App\Helpers\KeyGenerator::generateUniqueKey('GB');


                // Check collision
                $existing = LicenseModel::findByKey($licenseKey);
                if ($existing) {
                    $skippedCount++;
                    $errors[] = "Row #" . ($index + 1) . ": Key '{$licenseKey}' already exists.";
                    continue;
                }

                // Customer creation or resolution
                $customerId = null;
                if (!empty($row['customer_email'])) {
                    $custEmail = trim($row['customer_email']);
                    $cust = DatabaseConnection::fetchOne("SELECT id FROM `customers` WHERE email = ?", [$custEmail]);
                    if ($cust) {
                        $customerId = (int) $cust['id'];
                    } else {
                        $custUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $stmtCust = $db->prepare("INSERT INTO `customers` (`uuid`, `name`, `email`, `company`, `created_at`) VALUES (?, ?, ?, ?, NOW())");
                        $stmtCust->execute([$custUuid, $row['customer_name'] ?? 'Imported Customer', $custEmail, $row['company'] ?? null]);
                        $customerId = (int) $db->lastInsertId();
                    }
                }

                $licenseUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $allowedDevices = (int) ($row['allowed_devices'] ?? 1);
                $expiryDate     = !empty($row['expiry_date']) ? $row['expiry_date'] : date('Y-m-d H:i:s', strtotime('+30 days'));

                $stmtLic = $db->prepare("INSERT INTO `licenses` (`uuid`, `product_id`, `license_key`, `customer_id`, `plan_id`, `created_by`, `status`, `expiry_date`, `allowed_devices`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())");
                $stmtLic->execute([
                    $licenseUuid,
                    $productId,
                    $licenseKey,
                    $customerId,
                    $planId,
                    $currentUser['id'],
                    $expiryDate,
                    $allowedDevices
                ]);

                $importedCount++;
            } catch (Exception $e) {
                $skippedCount++;
                $errors[] = "Row #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        AuditLoggerService::log(
            $currentUser['id'],
            'license.import',
            "Imported {$importedCount} licenses (skipped: {$skippedCount})",
            'licenses',
            null,
            ['imported' => $importedCount, 'skipped' => $skippedCount],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success([
            'imported_count' => $importedCount,
            'skipped_count'  => $skippedCount,
            'errors'         => $errors
        ], "Bulk CSV import processed");
    }
}


