<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;
use App\Services\AuditLoggerService;

class AuthController
{
    /**
     * GET /login
     * Render Login View
     */
    public function showLogin(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If already logged in, redirect to dashboard
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }

        $csrfToken = SecurityHelper::generateCsrfToken();
        $siteName = $_ENV['APP_NAME'] ?? 'Enterprise License Manager';

        require __DIR__ . '/../../../views/auth/login.php';
    }

    /**
     * GET /logout
     * Web Logout Handler
     */
    public function logout(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            AuditLoggerService::log(
                $_SESSION['user_id'],
                'auth.web_logout',
                "User logged out via web panel",
                'users',
                $_SESSION['user_id']
            );
        }

        session_unset();
        session_destroy();

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
        exit;
    }
}
