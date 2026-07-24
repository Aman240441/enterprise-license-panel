<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Database\DatabaseConnection;
use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\RBACMiddleware;
use App\Models\UserModel;
use App\Services\AuditLoggerService;

class UserApiController
{
    /**
     * GET /api/v1/users/list
     */
    public function list(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('users.manage');

        $params = $request->getParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 25);

        $result = UserModel::search($params, $page, $perPage);
        ResponseHelper::success($result['items'], "Users retrieved", 200, [
            'total'       => $result['total'],
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total_pages' => $result['total_pages']
        ]);
    }

    /**
     * POST /api/v1/users/create
     * Create Employee / Admin / User Account
     */
    public function create(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('users.manage');

        $body = $request->getBody();

        $validator = Validator::make($body, [
            'full_name' => 'required|min:3',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role_slug' => 'required|in:admin,employee,viewer,reseller'
        ]);

        if ($validator->fails()) {
            ResponseHelper::error("Validation failed", 422, $validator->errors());
        }

        $role = DatabaseConnection::fetchOne("SELECT id FROM `roles` WHERE slug = ?", [$body['role_slug']]);
        if (!$role) {
            ResponseHelper::error("Selected role is invalid.", 400);
        }

        $uuid = SecurityHelper::generateUuid();
        $passwordHash = SecurityHelper::hashPassword($body['password']);
        $dailyLimit = (int) ($body['daily_gen_limit'] ?? 0);
        $monthlyLimit = (int) ($body['monthly_gen_limit'] ?? 0);
        $allowedPlansJson = !empty($body['allowed_plans']) ? json_encode($body['allowed_plans']) : null;

        DatabaseConnection::query("
            INSERT INTO `users` (`uuid`, `role_id`, `full_name`, `email`, `password_hash`, `status`, `daily_gen_limit`, `monthly_gen_limit`, `allowed_plans_json`)
            VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)
        ", [
            $uuid,
            $role['id'],
            trim($body['full_name']),
            strtolower(trim($body['email'])),
            $passwordHash,
            $dailyLimit,
            $monthlyLimit,
            $allowedPlansJson
        ]);

        $userId = (int) DatabaseConnection::lastInsertId();

        // Custom Permissions Overrides
        if (!empty($body['custom_permissions']) && is_array($body['custom_permissions'])) {
            foreach ($body['custom_permissions'] as $permSlug => $granted) {
                $perm = DatabaseConnection::fetchOne("SELECT id FROM `permissions` WHERE slug = ?", [$permSlug]);
                if ($perm) {
                    DatabaseConnection::query("
                        INSERT INTO `user_permissions` (`user_id`, `permission_id`, `is_granted`)
                        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `is_granted` = VALUES(`is_granted`)
                    ", [$userId, $perm['id'], $granted ? 1 : 0]);
                }
            }
        }

        AuditLoggerService::log(
            $currentUser['id'],
            'user.create',
            "Created account {$body['email']} with role {$body['role_slug']}",
            'users',
            $userId,
            ['email' => $body['email'], 'role' => $body['role_slug']],
            201,
            $currentUser['role_slug']
        );

        ResponseHelper::created(['id' => $userId, 'uuid' => $uuid, 'email' => $body['email']], "User account created successfully.");
    }

    /**
     * PUT /api/v1/users/update
     * Update user profile, role, quotas, status
     */
    public function update(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('users.manage');

        $body = $request->getBody();
        $userId = (int) ($body['user_id'] ?? 0);

        $user = UserModel::findById($userId);
        if (!$user) {
            ResponseHelper::notFound("User account not found.");
        }

        $fullName = !empty($body['full_name']) ? trim($body['full_name']) : $user['full_name'];
        $status = !empty($body['status']) ? $body['status'] : $user['status'];
        $dailyLimit = isset($body['daily_gen_limit']) ? (int)$body['daily_gen_limit'] : (int)$user['daily_gen_limit'];
        $monthlyLimit = isset($body['monthly_gen_limit']) ? (int)$body['monthly_gen_limit'] : (int)$user['monthly_gen_limit'];

        DatabaseConnection::query("
            UPDATE `users` 
            SET `full_name` = ?, `status` = ?, `daily_gen_limit` = ?, `monthly_gen_limit` = ?, `updated_at` = NOW()
            WHERE `id` = ?
        ", [$fullName, $status, $dailyLimit, $monthlyLimit, $userId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'user.update',
            "Updated user profile for {$user['email']}",
            'users',
            $userId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "User account updated successfully.");
    }

    /**
     * POST /api/v1/users/reset-password
     */
    public function resetPassword(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('users.manage');

        $body = $request->getBody();
        $userId = (int) ($body['user_id'] ?? 0);
        $newPassword = $body['new_password'] ?? '';

        if (empty($newPassword) || strlen($newPassword) < 6) {
            ResponseHelper::error("New password must be at least 6 characters.", 400);
        }

        $user = UserModel::findById($userId);
        if (!$user) {
            ResponseHelper::notFound("User not found.");
        }

        $hash = SecurityHelper::hashPassword($newPassword);
        DatabaseConnection::query("UPDATE `users` SET `password_hash` = ?, `failed_login_attempts` = 0, `lockout_until` = NULL WHERE `id` = ?", [$hash, $userId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'user.reset_password',
            "Reset password for user {$user['email']}",
            'users',
            $userId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "Password reset successfully.");
    }

    /**
     * DELETE /api/v1/users/delete
     */
    public function delete(Request $request): void
    {
        $currentUser = AuthMiddleware::handle();
        RBACMiddleware::requirePermission('users.manage');

        $body = $request->getBody();
        $userId = (int) ($body['user_id'] ?? 0);

        if ($userId === $currentUser['id']) {
            ResponseHelper::error("Cannot delete your own active account.", 400);
        }

        $user = UserModel::findById($userId);
        if (!$user) {
            ResponseHelper::notFound("User not found.");
        }

        DatabaseConnection::query("UPDATE `users` SET `deleted_at` = NOW(), `status` = 'suspended' WHERE `id` = ?", [$userId]);

        AuditLoggerService::log(
            $currentUser['id'],
            'user.delete',
            "Soft-deleted user account {$user['email']}",
            'users',
            $userId,
            null,
            200,
            $currentUser['role_slug']
        );

        ResponseHelper::success(null, "User deleted successfully.");
    }
}
