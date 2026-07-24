<?php

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DeviceController;
use App\Controllers\Admin\LicenseController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Core\Router;

/** @var Router $router */

$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/licenses', [LicenseController::class, 'index']);
$router->get('/licenses/create', [LicenseController::class, 'create']);

$router->get('/devices', [DeviceController::class, 'index']);

$router->get('/employees', [UserController::class, 'index']);
$router->get('/settings', [SettingsController::class, 'index']);
