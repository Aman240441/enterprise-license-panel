<?php

namespace App\Middleware;

use App\Helpers\ResponseHelper;
use App\Services\RBACService;

class RBACMiddleware
{
    /**
     * Ensure current user has explicit permission slug
     */
    public static function requirePermission(string $permissionSlug): void
    {
        $user = AuthMiddleware::handle();
        if (!RBACService::hasPermission($user['id'], $permissionSlug)) {
            ResponseHelper::forbidden("Access Denied: You lack the required permission ('{$permissionSlug}') to perform this action.");
        }
    }

    /**
     * Ensure current user belongs to one of allowed role slugs
     */
    public static function requireRole(string ...$allowedRoleSlugs): void
    {
        $user = AuthMiddleware::handle();
        if (!in_array($user['role_slug'], $allowedRoleSlugs, true)) {
            ResponseHelper::forbidden("Access Denied: Your assigned role ('{$user['role_name']}') is not authorized for this resource.");
        }
    }
}
