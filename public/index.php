<?php

/**
 * Enterprise License Management System
 * Front Controller & Application Entry Point
 */

declare(strict_types=1);

error_reporting(E_ALL);

// Load Autoloader
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Load Environment Variables
\App\Config\Env::load(__DIR__ . '/../.env');

// Ensure storage subdirectories exist
$storageDirs = [__DIR__ . '/../storage/uploads', __DIR__ . '/../storage/logs', __DIR__ . '/../storage/backups'];
foreach ($storageDirs as $sDir) {
    if (!is_dir($sDir)) {
        @mkdir($sDir, 0777, true);
    }
}

// Dynamically compute APP_URL based on current host, reverse proxy headers, & path
if (isset($_SERVER['HTTP_HOST'])) {
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $subPath = ($scriptDir === '' || $scriptDir === '/') ? '' : $scriptDir;
    if (empty($_ENV['APP_URL'])) {
        $_ENV['APP_URL'] = "{$scheme}://{$host}{$subPath}";
    }
}


// Set Application Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Apply Enterprise Security Headers (CSP, HSTS, CORS, Clickjacking protection)
\App\Helpers\SecurityHelper::applySecurityHeaders();

// Handle Global Middlewares
\App\Middleware\InputSanitizerMiddleware::handle();
\App\Middleware\RateLimitMiddleware::handle(120);

// Initialize Database Schema and Default Seeds automatically if not initialized
try {
    \App\Database\Schema::up();
    \App\Database\Seeder::run();
} catch (\Throwable $e) {
    error_log("Schema/Seeder auto-bootstrap log: " . $e->getMessage());
}

// Initialize Router & Request
$request = new \App\Core\Request();
$router = new \App\Core\Router();

// Load Route Files
require_once __DIR__ . '/../routes/api.php';
require_once __DIR__ . '/../routes/web.php';

// Dispatch Request
$router->dispatch($request);
