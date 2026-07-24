<?php

namespace App\Helpers;

use App\Database\DatabaseConnection;
use Exception;

class SecurityHelper
{
    /**
     * Send Enterprise Security Headers (CSP, HSTS, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection)
     */
    public static function applySecurityHeaders(): void
    {
        if (headers_sent()) return;

        // CORS
        $allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
        header("Access-Control-Allow-Origin: {$allowedOrigins}");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // HSTS (Strict-Transport-Security)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // CSP (Content-Security-Policy)
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' http: https: data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https: blob:; connect-src 'self' http: https: ws: wss:;");

        // Clickjacking & MIME sniffing protection
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }

    /**
     * Hash password using Argon2id with BCRYPT fallback
     */
    public static function hashPassword(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            $options = [
                'memory_cost' => (int) ($_ENV['ARGON2_MEMORY_COST'] ?? 65536),
                'time_cost'   => (int) ($_ENV['ARGON2_TIME_COST'] ?? 4),
                'threads'     => (int) ($_ENV['ARGON2_THREADS'] ?? 2)
            ];
            return password_hash($password, PASSWORD_ARGON2ID, $options);
        }

        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify plain password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if account is locked out due to excessive failed attempts
     */
    public static function checkAccountLockout(array $user): ?string
    {
        if (!empty($user['lockout_until'])) {
            $lockoutTime = strtotime($user['lockout_until']);
            if (time() < $lockoutTime) {
                $minutesRemaining = ceil(($lockoutTime - time()) / 60);
                return "Account locked out due to multiple failed login attempts. Please try again in {$minutesRemaining} minutes.";
            } else {
                // Lockout expired, reset counter
                DatabaseConnection::query(
                    "UPDATE `users` SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?",
                    [$user['id']]
                );
            }
        }
        return null;
    }

    /**
     * Record a failed login attempt and trigger lockout if threshold is exceeded
     */
    public static function recordFailedLogin(int $userId): void
    {
        $maxAttempts = 5;
        $lockoutMinutes = 15;

        $user = DatabaseConnection::fetchOne("SELECT failed_login_attempts FROM `users` WHERE id = ?", [$userId]);
        if (!$user) return;

        $attempts = $user['failed_login_attempts'] + 1;
        if ($attempts >= $maxAttempts) {
            $lockoutUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
            DatabaseConnection::query(
                "UPDATE `users` SET failed_login_attempts = ?, lockout_until = ? WHERE id = ?",
                [$attempts, $lockoutUntil, $userId]
            );
        } else {
            DatabaseConnection::query(
                "UPDATE `users` SET failed_login_attempts = ? WHERE id = ?",
                [$attempts, $userId]
            );
        }
    }

    /**
     * Reset failed login counter upon successful authentication
     */
    public static function resetFailedLogins(int $userId): void
    {
        DatabaseConnection::query(
            "UPDATE `users` SET failed_login_attempts = 0, lockout_until = NULL, last_login_at = NOW() WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Generate 2FA Secret Key (Base32 16 chars)
     */
    public static function generate2FASecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate UUID v4
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Sanitize string against XSS
     */
    public static function sanitize(?string $input): string
    {
        if ($input === null) return '';
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generate CSRF Token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF Token
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize request input array recursively
     */
    public static function sanitizeInputArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $key = self::sanitize($key);
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeInputArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Compute SHA-256 hash of a string
     */
    public static function sha256(string $data): string
    {
        return hash('sha256', $data);
    }
}
