<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Helpers\Validator;
use App\Models\DeviceModel;
use App\Models\LicenseModel;
use App\Services\AuditLoggerService;
use App\Services\DeviceManagerService;
use Exception;

class ClientApiController
{
    /**
     * POST /api/v1/license/activate
     * Public Hardware / Browser Device Activation API for External Apps & Extensions
     */
    /**
     * POST /api/v1/license/activate
     * Public Hardware / Browser Device Activation API for External Apps & Extensions
     */
    public function activate(Request $request): void
    {
        $body = $request->getBody();

        // 1. Validate Input (license_key & device_fingerprint required)
        $licenseKey = isset($body['license_key']) ? trim((string)$body['license_key']) : '';
        $deviceFingerprint = isset($body['device_fingerprint']) ? trim((string)$body['device_fingerprint']) : '';
        $productId = isset($body['product_id']) && is_numeric($body['product_id']) ? (int)$body['product_id'] : 0;

        if (empty($licenseKey)) {
            ResponseHelper::error("license_key is required.", 422, [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => 'empty(license_key)'
                ]
            ]);
        }

        if (empty($deviceFingerprint)) {
            ResponseHelper::error("device_fingerprint is required.", 422, [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => 'empty(device_fingerprint)'
                ]
            ]);
        }

        // 2. Lookup License Key in Database (Case-Insensitive & Trimmed)
        $license = LicenseModel::findByKey($licenseKey);
        if (!$license) {
            AuditLoggerService::log(null, 'client.activate_failed', "Invalid license key attempt: {$licenseKey}", 'licenses', null, $body, 404);
            ResponseHelper::error("License key '{$licenseKey}' was not found in the system database.", 404, [
                'rejection_detail' => [
                    'file' => 'LicenseModel.php',
                    'function' => 'findByKey',
                    'line' => 34,
                    'condition' => "SELECT FROM licenses WHERE UPPER(TRIM(license_key)) = '" . strtoupper($licenseKey) . "' returned 0 rows"
                ]
            ]);
        }

        // 3. Verify Bound Product & Optional Product Binding Match
        $boundProductId = (int)$license['product_id'];
        $product = DatabaseConnection::fetchOne("SELECT * FROM `products` WHERE id = ?", [$boundProductId]);
        if (!$product || ($product['status'] ?? '') !== 'active') {
            ResponseHelper::error("Product bound to license is inactive or invalid.", 400, [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "product_id {$boundProductId} status is not active"
                ]
            ]);
        }

        if ($productId > 0 && $productId !== $boundProductId) {
            AuditLoggerService::log(null, 'client.activate_mismatch', "License key product mismatch: {$licenseKey}", 'licenses', $license['id'], $body, 400);
            ResponseHelper::error("License key is registered for product '{$license['product_name']}' (ID: {$boundProductId}), but request passed product_id {$productId}.", 400, [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "request product_id ({$productId}) != license product_id ({$boundProductId})"
                ]
            ]);
        }

        // 4. Verify License Status (Revoked, Suspended, Inactive, Expired)
        if ($license['status'] === 'revoked') {
            ResponseHelper::forbidden("License key has been revoked by administration.", [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "license status == 'revoked'"
                ]
            ]);
        }

        if ($license['status'] === 'suspended') {
            ResponseHelper::forbidden("License key is currently suspended.", [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "license status == 'suspended'"
                ]
            ]);
        }

        if ($license['status'] === 'inactive') {
            ResponseHelper::forbidden("License key is inactive.", [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "license status == 'inactive'"
                ]
            ]);
        }

        // Check Expiration
        if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) <= time()) {
            LicenseModel::updateStatus($license['id'], 'expired');
            ResponseHelper::forbidden("License key expired on " . date('Y-m-d H:i:s', strtotime($license['expiry_date'])), [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'activate',
                    'line' => __LINE__,
                    'condition' => "expiry_date (" . $license['expiry_date'] . ") <= current_time"
                ]
            ]);
        }

        // 5. Execute Device Activation & Issue Session Token
        try {
            $activationResult = DeviceManagerService::activateDevice($license, $body);

            AuditLoggerService::log(
                null,
                'client.activate_success',
                "Activated device {$deviceFingerprint} for key {$licenseKey}",
                'licenses',
                $license['id'],
                ['fingerprint' => $deviceFingerprint, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'],
                200
            );

            ResponseHelper::success([
                'activated'          => true,
                'license_key'        => $license['license_key'],
                'status'             => 'active',
                'product_id'         => $boundProductId,
                'product_name'       => $license['product_name'],
                'plan_name'          => $license['plan_name'],
                'license_type'       => $license['license_type'],
                'session_token'      => $activationResult['session_token'],
                'expiry_date'        => $license['expiry_date'],
                'allowed_devices'    => $activationResult['allowed_devices'],
                'current_devices'    => $activationResult['current_devices'],
                'upload_permission'  => (bool) $license['upload_permission']
            ], "License activated successfully");
        } catch (Exception $e) {
            AuditLoggerService::log(null, 'client.activate_limit_exceeded', $e->getMessage(), 'licenses', $license['id'], $body, 400);
            ResponseHelper::error($e->getMessage(), 400, [
                'rejection_detail' => [
                    'file' => 'DeviceManagerService.php',
                    'function' => 'activateDevice',
                    'line' => 43,
                    'condition' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * POST /api/v1/license/check
     * Real-time license status validation API
     */
    public function check(Request $request): void
    {
        $body = $request->getBody();
        $licenseKey = trim((string)($body['license_key'] ?? ''));
        $productId = isset($body['product_id']) && is_numeric($body['product_id']) ? (int)$body['product_id'] : 0;

        if (empty($licenseKey)) {
            ResponseHelper::error("license_key is required.", 400, [
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'check',
                    'line' => __LINE__,
                    'condition' => 'empty(license_key)'
                ]
            ]);
        }

        $license = LicenseModel::findByKey($licenseKey);
        if (!$license) {
            ResponseHelper::success([
                'valid'   => false,
                'reason'  => "License key '{$licenseKey}' not found",
                'rejection_detail' => [
                    'file' => 'LicenseModel.php',
                    'function' => 'findByKey',
                    'line' => 34,
                    'condition' => '0 DB rows matched license_key'
                ]
            ], "Validation check completed");
        }

        $boundProductId = (int)$license['product_id'];
        if ($productId > 0 && $productId !== $boundProductId) {
            ResponseHelper::success([
                'valid'   => false,
                'reason'  => "Product mismatch: License is for product ID {$boundProductId}, passed {$productId}",
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'check',
                    'line' => __LINE__,
                    'condition' => "request product_id ({$productId}) != license product_id ({$boundProductId})"
                ]
            ], "Validation check completed");
        }

        if (in_array($license['status'], ['revoked', 'suspended', 'inactive'], true)) {
            ResponseHelper::success([
                'valid'   => false,
                'status'  => $license['status'],
                'reason'  => "License status is '{$license['status']}'",
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'check',
                    'line' => __LINE__,
                    'condition' => "status == {$license['status']}"
                ]
            ], "Validation check completed");
        }

        if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) <= time()) {
            LicenseModel::updateStatus($license['id'], 'expired');
            ResponseHelper::success([
                'valid'   => false,
                'status'  => 'expired',
                'reason'  => 'License key has expired on ' . $license['expiry_date'],
                'rejection_detail' => [
                    'file' => 'ClientApiController.php',
                    'function' => 'check',
                    'line' => __LINE__,
                    'condition' => "expiry_date ({$license['expiry_date']}) <= current_time"
                ]
            ], "Validation check completed");
        }

        ResponseHelper::success([
            'valid'              => true,
            'status'             => 'active',
            'product_id'         => $boundProductId,
            'product_name'       => $license['product_name'],
            'plan_name'          => $license['plan_name'],
            'license_type'       => $license['license_type'],
            'expiry_date'        => $license['expiry_date'],
            'allowed_devices'    => (int) $license['allowed_devices'],
            'current_devices'    => (int) $license['current_devices'],
            'upload_permission'  => (bool) $license['upload_permission']
        ], "License is active and valid");
    }

    /**
     * POST /api/v1/license/refresh-session
     * Heartbeat / Session Renewal Endpoint
     */
    public function refreshSession(Request $request): void
    {
        $body = $request->getBody();
        $licenseKey = trim($body['license_key'] ?? '');
        $fingerprint = trim($body['device_fingerprint'] ?? '');

        if (empty($licenseKey) || empty($fingerprint)) {
            ResponseHelper::error("license_key and device_fingerprint are required.", 400);
        }

        $license = LicenseModel::findByKey($licenseKey);
        if (!$license || $license['status'] !== 'active') {
            ResponseHelper::forbidden("License is inactive, expired, or invalid.");
        }

        $device = DeviceModel::findByFingerprint($license['id'], $fingerprint);
        if (!$device) {
            ResponseHelper::notFound("Device fingerprint not activated for this license key.");
        }

        $newSessionToken = bin2hex(random_bytes(32));
        DeviceModel::updateLastSeen($device['id'], $newSessionToken);

        ResponseHelper::success([
            'session_refreshed' => true,
            'session_token'     => $newSessionToken,
            'last_seen'         => date('c')
        ], "Session heartbeat refreshed");
    }

    /**
     * POST /api/v1/upload-authorize
     * Authorization endpoint for Chrome Extension file uploads to Supabase/Storage
     */
    public function uploadAuthorize(Request $request): void
    {
        $body = $request->getBody();

        // Extract key, session, device from headers or body payload
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $headerMap = [];
        foreach ($headers as $k => $v) {
            $headerMap[strtolower($k)] = $v;
        }

        $licenseKey = trim($body['license_key'] ?? $headerMap['x-license-key'] ?? '');
        $sessionId  = trim($body['session_id'] ?? $body['session_token'] ?? $headerMap['x-session-id'] ?? '');
        $deviceId   = trim($body['device_id'] ?? $body['device_fingerprint'] ?? $headerMap['x-device-id'] ?? '');
        $fileName   = trim($body['file_name'] ?? 'upload_' . time() . '.bin');
        $fileSize   = (int) ($body['file_size'] ?? 0);
        $mimeType   = trim($body['mime_type'] ?? 'application/octet-stream');

        if (empty($licenseKey)) {
            ResponseHelper::error("license_key or x-license-key header is required.", 400);
        }

        // 1. Fetch & Validate License
        $license = LicenseModel::findByKey($licenseKey);
        if (!$license) {
            AuditLoggerService::log(null, 'upload.unauthorized', "Upload attempt with invalid license key: {$licenseKey}", 'licenses', null, $body, 404);
            ResponseHelper::notFound("Invalid license key.");
        }

        if ($license['status'] !== 'active') {
            AuditLoggerService::log(null, 'upload.forbidden_status', "Upload attempt with inactive key: {$licenseKey} (status: {$license['status']})", 'licenses', $license['id'], $body, 403);
            ResponseHelper::forbidden("License status is '{$license['status']}'. Upload access denied.");
        }

        if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) <= time()) {
            LicenseModel::updateStatus($license['id'], 'expired');
            ResponseHelper::forbidden("License key expired on " . $license['expiry_date']);
        }

        // 2. Check Upload Permission
        if (empty($license['upload_permission'])) {
            AuditLoggerService::log(null, 'upload.permission_denied', "License {$licenseKey} lacks upload_permission", 'licenses', $license['id'], $body, 403);
            ResponseHelper::forbidden("Upload permission is disabled for this license key.");
        }

        // 3. Verify Device Binding if fingerprint provided
        if (!empty($deviceId)) {
            $device = DeviceModel::findByFingerprint($license['id'], $deviceId);
            if (!$device || !$device['is_active']) {
                ResponseHelper::forbidden("Device fingerprint is not activated or enabled for this license.");
            }
        }

        // 4. Determine Active Storage Driver & Prepare Upload Meta
        $driverName = $_ENV['STORAGE_DRIVER'] ?? 'local';
        $uploadUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $targetPath = "uploads/" . date('Y/m/') . $uploadUuid . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);

        // Record in Database
        $sql = "INSERT INTO uploads (uuid, license_id, customer_id, file_name, file_path, storage_driver, file_size, mime_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        DatabaseConnection::query($sql, [
            $uploadUuid,
            $license['id'],
            $license['customer_id'] ?? null,
            $fileName,
            $targetPath,
            $driverName,
            $fileSize,
            $mimeType,
            $license['created_by'] ?? 1
        ]);
        $uploadId = DatabaseConnection::lastInsertId();

        AuditLoggerService::log(
            null,
            'upload.authorized',
            "Authorized upload for key {$licenseKey} file {$fileName} ({$fileSize} bytes)",
            'uploads',
            (int)$uploadId,
            ['license_key' => $licenseKey, 'uuid' => $uploadUuid, 'driver' => $driverName],
            200
        );

        $baseUrl = rtrim($_ENV['APP_URL'] ?? $_ENV['API_BASE_URL'] ?? '', '/');
        ResponseHelper::success([
            'authorized'      => true,
            'upload_uuid'     => $uploadUuid,
            'license_key'     => $licenseKey,
            'storage_driver'  => $driverName,
            'destination_path'=> $targetPath,
            'upload_endpoint' => $baseUrl,
            'expires_in_sec'  => 3600
        ], "Upload authorization granted");
    }
}


