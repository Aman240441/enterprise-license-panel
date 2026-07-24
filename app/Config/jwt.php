<?php

namespace App\Config;

return [
    'secret' => Env::get('JWT_SECRET', 'secret_key_change_in_production_123456789'),
    'algo' => Env::get('JWT_ALGO', 'HS256'),
    'access_token_expiry' => (int) Env::get('JWT_ACCESS_EXPIRY', 3600), // 1 hour
    'refresh_token_expiry' => (int) Env::get('JWT_REFRESH_EXPIRY', 604800), // 7 days
    'issuer' => Env::get('APP_NAME', 'Enterprise License Manager'),
];
