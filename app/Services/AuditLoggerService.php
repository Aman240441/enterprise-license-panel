<?php

namespace App\Services;

use App\Database\DatabaseConnection;
use App\Helpers\SecurityHelper;

class AuditLoggerService
{
    /**
     * Parse simple User Agent to detect Browser and OS
     */
    private static function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // OS Detection
        if (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/windows nt 6.3/i', $ua)) $os = 'Windows 8.1';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $os = 'macOS';
        elseif (preg_match('/linux/i', $ua)) $os = 'Linux';
        elseif (preg_match('/android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iphone|ipad/i', $ua)) $os = 'iOS';

        // Browser Detection
        if (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) $browser = 'Safari';

        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * Enriched Enterprise Audit Logger
     */
    public static function log(
        ?int $userId,
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $payload = null,
        int $responseCode = 200,
        ?string $userRole = null
    ): void {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $endpoint = $_SERVER['REQUEST_URI'] ?? '/';

            $parsedUa = self::parseUserAgent($userAgent);
            $payloadJson = $payload !== null ? json_encode(SecurityHelper::sanitizeInputArray($payload)) : null;

            DatabaseConnection::query(
                "INSERT INTO `activity_logs` 
                 (`user_id`, `user_role`, `action`, `endpoint`, `entity_type`, `entity_id`, `description`, `ip_address`, `browser`, `os`, `user_agent`, `response_code`, `payload_json`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $userRole,
                    $action,
                    $endpoint,
                    $entityType,
                    $entityId,
                    $description,
                    $ipAddress,
                    $parsedUa['browser'],
                    $parsedUa['os'],
                    $userAgent,
                    $responseCode,
                    $payloadJson
                ]
            );
        } catch (\Throwable $e) {
            error_log("AuditLogger Exception: " . $e->getMessage());
        }
    }

    /**
     * Log authentication login attempt (success, failure, or locked out)
     */
    public static function logLogin(?int $userId, string $emailAttempted, string $status, ?string $failureReason = null): void
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            DatabaseConnection::query(
                "INSERT INTO `login_logs` (`user_id`, `email_attempted`, `status`, `failure_reason`, `ip_address`, `user_agent`)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$userId, $emailAttempted, $status, $failureReason, $ipAddress, $userAgent]
            );
        } catch (\Throwable $e) {
            error_log("LoginLogger Exception: " . $e->getMessage());
        }
    }
}
