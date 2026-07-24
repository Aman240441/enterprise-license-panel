<?php

namespace App\Middleware;

use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;

class CSRFMiddleware
{
    /**
     * Enforce CSRF token verification for state-changing HTTP methods (POST, PUT, DELETE, PATCH)
     */
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            // Check if request is API JSON with Bearer authorization header (API clients bypass session CSRF)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            if (str_starts_with($authHeader, 'Bearer ')) {
                return;
            }

            // Retrieve token from POST payload or Header
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!SecurityHelper::validateCsrfToken($token)) {
                ResponseHelper::forbidden("CSRF validation failed. Invalid or missing CSRF token.");
            }
        }
    }
}
