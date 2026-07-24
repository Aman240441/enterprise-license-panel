<?php

namespace App\Middleware;

use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Services\JWTService;

class AuthMiddleware
{
    private static ?array $currentUser = null;

    /**
     * Authenticate request via JWT Bearer header or Session
     */
    public static function handle(): array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        // 1. Try Bearer Token header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = trim($matches[1]);
            $payload = JWTService::validateToken($jwt);

            if ($payload && isset($payload['sub'])) {
                $user = DatabaseConnection::fetchOne("
                    SELECT u.id, u.uuid, u.full_name, u.email, u.status, u.role_id, r.slug as role_slug, r.name as role_name,
                           u.daily_gen_limit, u.monthly_gen_limit, u.allowed_plans_json
                    FROM `users` u
                    JOIN `roles` r ON u.role_id = r.id
                    WHERE u.id = ? AND u.status = 'active'
                ", [$payload['sub']]);

                if ($user) {
                    self::$currentUser = $user;
                    return $user;
                }
            }

            ResponseHelper::unauthorized("Invalid, expired, or revoked API Bearer Token.");
        }

        // 2. Try Web Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            $user = DatabaseConnection::fetchOne("
                SELECT u.id, u.uuid, u.full_name, u.email, u.status, u.role_id, r.slug as role_slug, r.name as role_name,
                       u.daily_gen_limit, u.monthly_gen_limit, u.allowed_plans_json
                FROM `users` u
                JOIN `roles` r ON u.role_id = r.id
                WHERE u.id = ? AND u.status = 'active'
            ", [$_SESSION['user_id']]);

            if ($user) {
                self::$currentUser = $user;
                return $user;
            }
        }

        ResponseHelper::unauthorized("Authentication required. Please log in.");
        exit;
    }

    /**
     * Get currently authenticated user array
     */
    public static function user(): ?array
    {
        return self::$currentUser;
    }
}
