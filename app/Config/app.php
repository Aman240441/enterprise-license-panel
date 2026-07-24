<?php

namespace App\Config;

return [
    'name' => Env::get('APP_NAME', 'Enterprise License Manager'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::get('APP_DEBUG', false),
    'url' => Env::get('APP_URL', 'http://localhost'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
    'default_prefix' => Env::get('DEFAULT_LICENSE_PREFIX', 'GB'),
    'default_expiry_days' => (int) Env::get('DEFAULT_EXPIRY_DAYS', 30),
    'rate_limit_per_minute' => (int) Env::get('RATE_LIMIT_PER_MINUTE', 120),
    'argon2' => [
        'memory_cost' => (int) Env::get('ARGON2_MEMORY_COST', 65536),
        'time_cost' => (int) Env::get('ARGON2_TIME_COST', 4),
        'threads' => (int) Env::get('ARGON2_THREADS', 2),
    ]
];
