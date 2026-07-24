<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\RBACMiddleware;
use App\Models\DeviceModel;
use App\Models\LicenseModel;
use App\Services\AuditLoggerService;

class DeviceApiController
{
    /**
     * GET /api/v1/devices
     * Searchable/Filterable list of activated hardware & browser devices
     */
    public function list(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        $params = $request->getParams();

        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 25);
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $queryParams = [];

        if (!empty($params['license_id'])) {
            $where[] = "d.license_id = ?";
            $queryParams[] = (int) $params['license_id'];
        }

        if (!empty($params['search'])) {
            $term = '%' . trim($params['search']) . '%';
            $where[] = "(d.device_fingerprint LIKE ? OR d.ip_address LIKE ? OR l.license_key LIKE ? OR d.browser LIKE ? OR d.os LIKE ?)";
            array_push($queryParams, $term, $term, $term, $term, $term);
        }

        $whereSql = implode(" AND ", $where);

        $countRow = DatabaseConnection::fetchOne("
            SELECT COUNT(*) as total 
            FROM `devices` d
            JOIN `licenses` l ON d.license_id = l.id
            WHERE {$whereSql}
        ", $queryParams);
        $total = (int) ($countRow['total'] ?? 0);

        $devices = DatabaseConnection::fetchAll("
            SELECT d.*, l.license_key, p.name as product_name, c.name as customer_name
            FROM `devices` d
            JOIN `licenses` l ON d.license_id = l.id
            JOIN `products` p ON l.product_id = p.id
            LEFT JOIN `customers` c ON l.customer_id = c.id
            WHERE {$whereSql}
            ORDER BY d.last_seen DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $queryParams);

        ResponseHelper::success($devices, "Devices retrieved", 200, [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
    }

    /**
     * POST /api/v1/device/deactivate
     * Deactivate specific device fingerprint
     */
    public function deactivate(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('devices.manage');

        $body = $request->getBody();
        $deviceId = (int) ($body['device_id'] ?? 0);

        $device = DatabaseConnection::fetchOne("SELECT * FROM `devices` WHERE id = ?", [$deviceId]);
        if (!$device) {
            ResponseHelper::notFound("Device record not found.");
        }

        DeviceModel::deactivateDevice($deviceId);

        // Update active devices count on parent license
        $licenseId = (int) $device['license_id'];
        $newCount = DeviceModel::countActiveDevices($licenseId);
        DatabaseConnection::query("UPDATE `licenses` SET `current_devices` = ? WHERE id = ?", [$newCount, $licenseId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'device.deactivate',
            "Deactivated device {$device['device_fingerprint']} for license ID {$licenseId}",
            'devices',
            $deviceId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "Device deactivated successfully.");
    }

    /**
     * POST /api/v1/device/reset
     * Flush all activated devices for a license key
     */
    public function reset(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('devices.manage');

        $body = $request->getBody();
        $licenseId = (int) ($body['license_id'] ?? 0);

        $license = LicenseModel::findById($licenseId);
        if (!$license) {
            ResponseHelper::notFound("License key not found.");
        }

        $flushedCount = DeviceModel::resetDevicesForLicense($licenseId);

        AuditLoggerService::log(
            $currentUser['id'],
            'device.reset',
            "Reset {$flushedCount} devices for license {$license['license_key']}",
            'licenses',
            $licenseId,
            ['flushed_count' => $flushedCount],
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success([
            'flushed_count' => $flushedCount
        ], "All device activations reset successfully.");
    }

    /**
     * POST /api/v1/device/force-logout
     * Revoke active sessions for a device
     */
    public function forceLogout(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('devices.manage');

        $body = $request->getBody();
        $deviceId = (int) ($body['device_id'] ?? 0);

        $device = DatabaseConnection::fetchOne("SELECT * FROM `devices` WHERE id = ?", [$deviceId]);
        if (!$device) {
            ResponseHelper::notFound("Device record not found.");
        }

        DatabaseConnection::query("UPDATE `sessions` SET revoked_at = NOW() WHERE device_id = ?", [$deviceId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'device.force_logout',
            "Forced session logout for device ID {$deviceId}",
            'devices',
            $deviceId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "Device sessions force logged out successfully.");
    }
}
