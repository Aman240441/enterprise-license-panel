<?php

namespace App\Config;

use PDO;

$databaseUrl = Env::get('DATABASE_URL') ?? getenv('DATABASE_URL');
$driver   = strtolower(Env::get('DB_DRIVER', Env::get('DB_CONNECTION', 'mysql')));
$host     = Env::get('DB_HOST', '127.0.0.1');
$port     = Env::get('DB_PORT', '3306');
$database = Env::get('DB_DATABASE', 'enterprise_license_db');
$username = Env::get('DB_USERNAME', 'root');
$password = Env::get('DB_PASSWORD', '');
$charset  = Env::get('DB_CHARSET', 'utf8mb4');
$sslMode  = Env::get('DB_SSL_MODE', 'prefer');

// Parse DATABASE_URL if present (Railway, Render, Koyeb, Supabase, Neon)
if (!empty($databaseUrl)) {
    $parsed = parse_url($databaseUrl);
    if ($parsed && isset($parsed['scheme'])) {
        $scheme = strtolower($parsed['scheme']);
        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $driver = 'pgsql';
            $port = $parsed['port'] ?? '5432';
        } elseif ($scheme === 'mysql') {
            $driver = 'mysql';
            $port = $parsed['port'] ?? '3306';
        }
        $host     = $parsed['host'] ?? $host;
        $database = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $database;
        $username = $parsed['user'] ?? $username;
        $password = $parsed['pass'] ?? $password;
    }
}

// Driver specific default ports
if ($driver === 'pgsql' && ($port === '3306' || empty($port))) {
    $port = '5432';
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if ($driver === 'mysql') {
    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
}

return [
    'driver'   => $driver,
    'host'     => $host,
    'port'     => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset'  => $charset,
    'sslmode'  => $sslMode,
    'options'  => $options
];
