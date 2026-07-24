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
    public function activate(Request $request): void
    {
        $body = $request->getBody();

        // 1. Validate Input
        $validator = Validator::make($body, [
            'license_key'        => 'required',
            'product_id'         => 'required|integer',
            'device_fingerprint' => 'required'
        ]);

        if ($validator->fails()) {
            ResponseHelper::error("Validation failed", 422, $validator->errors());
        }

        $licenseKey = trim($body['license_key']);
        $productId = (int) $body['product_id'];

        // 2. Fetch Product & Verify Status
        $product = DatabaseConnection::fetchOne("SELECT * FROM `products` WHERE id = ?", [$productId]);
        if (!$product || $product['status'] !== 'active') {
            ResponseHelper::error("Product is invalid, inactive, or deprecated.", 400);
        }

        // 3. Fetch License Key & Verify Product Binding
        $license = LicenseModel::findByKey($licenseKey);
        if (!$license) {
            AuditLoggerService::log(null, 'client.activate_failed', "Invalid license key attempt: {$licenseKey}", 'licenses', null, $body, 404);
            ResponseHelper::notFound("License key is invalid.");
        }

        if ((int)$license['product_id'] !== $productId) {
            AuditLoggerService::log(null, 'client.activate_mismatch', "License key product mismatch: {$licenseKey}", 'licenses', $license['id'], $body, 400);
            ResponseHelper::error("License key is not authorized for this software product.", 400);
        }

        // 4. Verify License Status (Revoked, Suspended, Inactive, Expired)
        if ($license['status'] === 'revoked') {
            ResponseHelper::forbidden("License key has been revoked by administration.");
        }

        if ($license['status'] === 'suspended') {
            ResponseHelper::forbidden("License key is currently suspended.");
        }

        if ($license['status'] === 'inactive') {
            ResponseHelper::forbidden("License key is inactive.");
        }

        // Check Expiration
        if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) <= time()) {
            LicenseModel::updateStatus($license['id'], 'expired');
            ResponseHelper::forbidden("License key expired on " . date('Y-m-d H:i:s', strtotime($license['expiry_date'])));
        }

        // 5. Execute Device Activation
        try {
            $activationResult = DeviceManagerService::activateDevice($license, $body);

            AuditLoggerService::log(
                null,
                'client.activate_success',
                "Activated device {$body['device_fingerprint']} for key {$licenseKey}",
                'licenses',
                $license['id'],
                ['fingerprint' => $body['device_fingerprint'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'],
                200
            );

            ResponseHelper::success([
                'activated'          => true,
                'license_key'        => $licenseKey,
                'status'             => 'active',
                'product_name'       => $product['name'],
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
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/license/check
     * Real-time license status validation API
     */
    public function check(Request $request): void
    {
        $body = $request->getBody();
        $licenseKey = trim($body['license_key'] ?? '');
        $productId = (int) ($body['product_id'] ?? 0);

        if (empty($licenseKey) || $productId <= 0) {
            ResponseHelper::error("license_key and product_id are required.", 400);
        }

        $license = LicenseModel::findByKey($licenseKey);
        if (!$license || (int)$license['product_id'] !== $productId) {
            ResponseHelper::success([
                'valid'   => false,
                'reason'  => 'Invalid license key or product mismatch'
            ], "Validation check completed");
        }

        if (in_array($license['status'], ['revoked', 'suspended', 'inactive'], true)) {
            ResponseHelper::success([
                'valid'   => false,
                'status'  => $license['status'],
                'reason'  => "License is {$license['status']}"
            ], "Validation check completed");
        }

        if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) <= time()) {
            LicenseModel::updateStatus($license['id'], 'expired');
            ResponseHelper::success([
                'valid'   => false,
                'status'  => 'expired',
                'reason'  => 'License key has expired'
            ], "Validation check completed");
        }

        ResponseHelper::success([
            'valid'              => true,
            'status'             => 'active',
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


