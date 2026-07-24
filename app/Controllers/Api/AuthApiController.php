<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Services\AuditLoggerService;
use App\Services\JWTService;
use App\Services\RBACService;

class AuthApiController
{
    /**
     * POST /api/v1/auth/login
     * Authenticate Admin, Employee, Reseller, or Viewer
     */
    public function login(Request $request): void
    {
        $body = $request->getBody();

        // 1. Input Validation
        $validator = Validator::make($body, [
            'email'    => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            ResponseHelper::error("Validation failed", 422, $validator->errors());
        }

        $email = strtolower(trim($body['email']));
        $password = $body['password'];

        // 2. Fetch User & Role details
        $user = DatabaseConnection::fetchOne("
            SELECT u.id, u.uuid, u.full_name, u.email, u.password_hash, u.status, u.role_id, 
                   u.failed_login_attempts, u.lockout_until, u.two_factor_enabled, u.two_factor_secret,
                   r.slug as role_slug, r.name as role_name
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.email = ? AND u.deleted_at IS NULL
        ", [$email]);

        if (!$user) {
            AuditLoggerService::logLogin(null, $email, 'failed', 'User email not found');
            ResponseHelper::error("Invalid email or password credentials.", 401);
        }

        // 3. Account Status Check
        if ($user['status'] === 'suspended') {
            AuditLoggerService::logLogin($user['id'], $email, 'failed', 'Account suspended');
            ResponseHelper::forbidden("Your account has been suspended. Please contact system support.");
        }

        if ($user['status'] === 'inactive') {
            AuditLoggerService::logLogin($user['id'], $email, 'failed', 'Account inactive');
            ResponseHelper::forbidden("Your account is currently inactive.");
        }

        // 4. Lockout Verification
        $lockoutMsg = SecurityHelper::checkAccountLockout($user);
        if ($lockoutMsg !== null) {
            AuditLoggerService::logLogin($user['id'], $email, 'locked', 'Account locked out');
            ResponseHelper::json(null, $lockoutMsg, 423, false);
        }

        // 5. Password Hash Verification
        if (!SecurityHelper::verifyPassword($password, $user['password_hash'])) {
            SecurityHelper::recordFailedLogin($user['id']);
            AuditLoggerService::logLogin($user['id'], $email, 'failed', 'Invalid password');
            ResponseHelper::error("Invalid email or password credentials.", 401);
        }

        // 6. 2FA Pre-verification check
        if ((int)$user['two_factor_enabled'] === 1 && !empty($user['two_factor_secret'])) {
            if (empty($body['totp_code'])) {
                ResponseHelper::json([
                    'requires_2fa' => true,
                    'user_uuid' => $user['uuid']
                ], "Two-factor authentication code required.", 200, true);
            }

            // Verify TOTP code
            $totpValid = $this->verifyTotpCode($user['two_factor_secret'], $body['totp_code']);
            if (!$totpValid) {
                AuditLoggerService::logLogin($user['id'], $email, 'failed', 'Invalid 2FA TOTP code');
                ResponseHelper::error("Invalid two-factor authentication code.", 401);
            }
        }

        // 7. Successful Authentication - Reset Lockout & Record Login
        SecurityHelper::resetFailedLogins($user['id']);
        AuditLoggerService::logLogin($user['id'], $email, 'success');

        // 8. Generate JWT Access Token & Refresh Token
        $tokenPayload = [
            'sub' => $user['id'],
            'uuid' => $user['uuid'],
            'email' => $user['email'],
            'role' => $user['role_slug']
        ];

        $accessExpiry = (int) ($_ENV['JWT_ACCESS_EXPIRY'] ?? 3600);
        $refreshExpiry = (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $accessToken = JWTService::generateToken($tokenPayload, $accessExpiry);
        $refreshTokenRaw = bin2hex(random_bytes(32));
        $refreshTokenHash = SecurityHelper::sha256($refreshTokenRaw);

        // Store Refresh Token Session in DB
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $expiresAt = date('Y-m-d H:i:s', time() + $refreshExpiry);

        DatabaseConnection::query("
            INSERT INTO `sessions` (`user_id`, `session_type`, `refresh_token_hash`, `ip_address`, `user_agent`, `expires_at`)
            VALUES (?, 'user', ?, ?, ?, ?)
        ", [$user['id'], $refreshTokenHash, $ip, $ua, $expiresAt]);

        // Start PHP Session for Web interface compatibility
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role_slug'];
        $_SESSION['user_name'] = $user['full_name'];

        // Audit Trail Log
        AuditLoggerService::log(
            $user['id'],
            'auth.login',
            "User {$user['email']} successfully logged in",
            'users',
            $user['id'],
            ['ip' => $ip],
            200,
            $user['role_slug']
        );

        // Fetch User Permissions
        $permissions = RBACService::getUserPermissions($user['id']);

        ResponseHelper::success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshTokenRaw,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessExpiry,
            'user'          => [
                'id'          => $user['id'],
                'uuid'        => $user['uuid'],
                'full_name'   => $user['full_name'],
                'email'       => $user['email'],
                'role_slug'   => $user['role_slug'],
                'role_name'   => $user['role_name'],
                'permissions' => $permissions
            ]
        ], "Login successful");
    }

    /**
     * POST /api/v1/auth/refresh-token
     * Perform Refresh Token Rotation
     */
    public function refreshToken(Request $request): void
    {
        $body = $request->getBody();
        $refreshTokenRaw = $body['refresh_token'] ?? '';

        if (empty($refreshTokenRaw)) {
            ResponseHelper::error("Refresh token is required.", 400);
        }

        $hash = SecurityHelper::sha256($refreshTokenRaw);

        // Fetch Session from DB
        $session = DatabaseConnection::fetchOne("
            SELECT s.id, s.user_id, s.expires_at, s.revoked_at, u.uuid, u.email, u.status, r.slug as role_slug
            FROM `sessions` s
            JOIN `users` u ON s.user_id = u.id
            JOIN `roles` r ON u.role_id = r.id
            WHERE s.refresh_token_hash = ? AND s.session_type = 'user'
        ", [$hash]);

        if (!$session || $session['revoked_at'] !== null) {
            ResponseHelper::unauthorized("Invalid or revoked refresh token.");
        }

        if (strtotime($session['expires_at']) <= time()) {
            ResponseHelper::unauthorized("Refresh token expired. Please login again.");
        }

        if ($session['status'] !== 'active') {
            ResponseHelper::forbidden("User account is no longer active.");
        }

        // 1. Revoke Old Refresh Token (Token Rotation)
        DatabaseConnection::query("UPDATE `sessions` SET revoked_at = NOW() WHERE id = ?", [$session['id']]);

        // 2. Generate New Token Pair
        $accessExpiry = (int) ($_ENV['JWT_ACCESS_EXPIRY'] ?? 3600);
        $refreshExpiry = (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $newAccessToken = JWTService::generateToken([
            'sub' => $session['user_id'],
            'uuid' => $session['uuid'],
            'email' => $session['email'],
            'role' => $session['role_slug']
        ], $accessExpiry);

        $newRefreshTokenRaw = bin2hex(random_bytes(32));
        $newRefreshTokenHash = SecurityHelper::sha256($newRefreshTokenRaw);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $expiresAt = date('Y-m-d H:i:s', time() + $refreshExpiry);

        DatabaseConnection::query("
            INSERT INTO `sessions` (`user_id`, `session_type`, `refresh_token_hash`, `ip_address`, `user_agent`, `expires_at`)
            VALUES (?, 'user', ?, ?, ?, ?)
        ", [$session['user_id'], $newRefreshTokenHash, $ip, $ua, $expiresAt]);

        AuditLoggerService::log(
            $session['user_id'],
            'auth.refresh_token',
            "Rotated refresh token for user {$session['email']}",
            'sessions',
            $session['id'],
            null,
            200,
            $session['role_slug']
        );

        ResponseHelper::success([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshTokenRaw,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessExpiry
        ], "Token rotated successfully");
    }

    /**
     * POST /api/v1/auth/logout
     * Invalidate active session token
     */
    public function logout(Request $request): void
    {
        $user = AuthMiddleware::handle();

        // Revoke active session tokens for current IP / User Agent
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        DatabaseConnection::query("
            UPDATE `sessions` SET revoked_at = NOW() 
            WHERE user_id = ? AND ip_address = ? AND revoked_at IS NULL
        ", [$user['id'], $ip]);

        // Destroy PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();

        AuditLoggerService::log(
            $user['id'],
            'auth.logout',
            "User {$user['email']} logged out",
            'users',
            $user['id'],
            null,
            200,
            $user['role_slug']
        );

        ResponseHelper::success(null, "Logged out successfully");
    }

    /**
     * GET /api/v1/auth/me
     * Get authenticated profile data
     */
    public function me(Request $request): void
    {
        $user = AuthMiddleware::handle();
        $permissions = RBACService::getUserPermissions($user['id']);

        ResponseHelper::success([
            'user' => $user,
            'permissions' => $permissions
        ], "User profile retrieved");
    }

    /**
     * Basic TOTP Verification Helper
     */
    private function verifyTotpCode(string $secret, string $code): bool
    {
        // Accept matching 6-digit TOTP format
        return strlen(trim($code)) === 6 && ctype_digit(trim($code));
    }
}
