<?php

use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ClientApiController;
use App\Controllers\Api\DashboardApiController;
use App\Controllers\Api\DeviceApiController;
use App\Controllers\Api\LicenseApiController;
use App\Controllers\Api\LogApiController;
use App\Controllers\Api\SettingsApiController;
use App\Controllers\Api\UserApiController;
use App\Core\Router;

/** @var Router $router */

// System Health & Version API
$router->get('/api/v1/health', function ($request) {
    \App\Helpers\ResponseHelper::success([
        'status' => 'operational',
        'api_version' => 'v1',
        'platform' => $_ENV['APP_NAME'] ?? 'Enterprise License Manager',
        'php_version' => PHP_VERSION,
        'timestamp' => date('c')
    ], 'Platform API v1 Operational');
});

/*
|--------------------------------------------------------------------------
| Authentication REST APIs (Phase 2)
|--------------------------------------------------------------------------
*/
$router->post('/api/v1/auth/login', [AuthApiController::class, 'login']);
$router->post('/api/v1/auth/refresh-token', [AuthApiController::class, 'refreshToken']);
$router->post('/api/v1/auth/logout', [AuthApiController::class, 'logout']);
$router->get('/api/v1/auth/me', [AuthApiController::class, 'me']);

/*
|--------------------------------------------------------------------------
| License Management REST APIs (Phase 3)
|--------------------------------------------------------------------------
*/
$router->get('/api/v1/licenses', [LicenseApiController::class, 'list']);
$router->get('/api/v1/licenses/summary', [LicenseApiController::class, 'summary']);
$router->get('/api/v1/licenses/export', [LicenseApiController::class, 'export']);
$router->post('/api/v1/licenses/export', [LicenseApiController::class, 'export']);
$router->post('/api/v1/licenses/import', [LicenseApiController::class, 'import']);
$router->get('/api/v1/licenses/{id}', [LicenseApiController::class, 'show']);
$router->get('/api/v1/licenses/{id}/audit', [LicenseApiController::class, 'auditHistory']);
$router->post('/api/v1/licenses/generate', [LicenseApiController::class, 'generate']);
$router->post('/api/v1/licenses/bulk-generate', [LicenseApiController::class, 'bulkGenerate']);
$router->post('/api/v1/licenses/update', [LicenseApiController::class, 'update']);
$router->post('/api/v1/licenses/activate', [LicenseApiController::class, 'activate']);
$router->post('/api/v1/licenses/deactivate', [LicenseApiController::class, 'deactivate']);
$router->post('/api/v1/licenses/revoke', [LicenseApiController::class, 'revoke']);
$router->post('/api/v1/licenses/suspend', [LicenseApiController::class, 'suspend']);
$router->post('/api/v1/licenses/extend-expiry', [LicenseApiController::class, 'extendExpiry']);
$router->post('/api/v1/licenses/reset-devices', [LicenseApiController::class, 'resetDevices']);
$router->post('/api/v1/licenses/bulk-action', [LicenseApiController::class, 'bulkAction']);
$router->post('/api/v1/upload-authorize', [ClientApiController::class, 'uploadAuthorize']);
$router->delete('/api/v1/licenses/delete', [LicenseApiController::class, 'delete']);


/*
|--------------------------------------------------------------------------
| Public Client Activation & Real-Time Validation APIs (Phase 4)
|--------------------------------------------------------------------------
*/
$router->post('/api/v1/license/activate', [ClientApiController::class, 'activate']);
$router->post('/api/v1/license/check', [ClientApiController::class, 'check']);
$router->post('/api/v1/license/refresh-session', [ClientApiController::class, 'refreshSession']);

/*
|--------------------------------------------------------------------------
| Device Management REST APIs (Phase 5)
|--------------------------------------------------------------------------
*/
$router->get('/api/v1/devices', [DeviceApiController::class, 'list']);
$router->post('/api/v1/device/deactivate', [DeviceApiController::class, 'deactivate']);
$router->post('/api/v1/device/reset', [DeviceApiController::class, 'reset']);
$router->post('/api/v1/device/force-logout', [DeviceApiController::class, 'forceLogout']);

/*
|--------------------------------------------------------------------------
| Dashboard & Analytics REST APIs (Phase 6)
|--------------------------------------------------------------------------
*/
$router->get('/api/v1/dashboard', [DashboardApiController::class, 'getDashboard']);

/*
|--------------------------------------------------------------------------
| User, Settings & Logs REST APIs (Phase 7)
|--------------------------------------------------------------------------
*/
$router->get('/api/v1/users/list', [UserApiController::class, 'list']);
$router->post('/api/v1/users/create', [UserApiController::class, 'create']);
$router->put('/api/v1/users/update', [UserApiController::class, 'update']);
$router->post('/api/v1/users/update', [UserApiController::class, 'update']);
$router->delete('/api/v1/users/delete', [UserApiController::class, 'delete']);
$router->post('/api/v1/users/delete', [UserApiController::class, 'delete']);
$router->post('/api/v1/users/reset-password', [UserApiController::class, 'resetPassword']);

$router->get('/api/v1/settings', [SettingsApiController::class, 'getSettings']);
$router->post('/api/v1/settings/update', [SettingsApiController::class, 'updateSettings']);

$router->get('/api/v1/logs/activity', [LogApiController::class, 'activityLogs']);

/*
|--------------------------------------------------------------------------
| Direct Non-Versioned API Route Aliases (Specification Compliance)
|--------------------------------------------------------------------------
*/
$router->post('/api/login', [AuthApiController::class, 'login']);
$router->post('/api/logout', [AuthApiController::class, 'logout']);
$router->post('/api/generate-license', [LicenseApiController::class, 'generate']);
$router->post('/api/bulk-generate', [LicenseApiController::class, 'bulkGenerate']);
$router->post('/api/activate-license', [ClientApiController::class, 'activate']);
$router->post('/api/check-license', [ClientApiController::class, 'check']);
$router->post('/api/upload-authorize', [ClientApiController::class, 'uploadAuthorize']);
$router->post('/api/revoke-license', [LicenseApiController::class, 'revoke']);
$router->post('/api/suspend-license', [LicenseApiController::class, 'suspend']);
$router->delete('/api/delete-license', [LicenseApiController::class, 'delete']);
$router->post('/api/delete-license', [LicenseApiController::class, 'delete']);
$router->post('/api/create-user', [UserApiController::class, 'create']);
$router->post('/api/update-user', [UserApiController::class, 'update']);
$router->delete('/api/delete-user', [UserApiController::class, 'delete']);
$router->post('/api/delete-user', [UserApiController::class, 'delete']);
$router->get('/api/dashboard', [DashboardApiController::class, 'getDashboard']);

