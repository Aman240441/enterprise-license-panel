<?php

namespace App\Config;

return [
    'url' => Env::get('SUPABASE_URL', ''),
    'service_role_key' => Env::get('SUPABASE_SERVICE_ROLE_KEY', ''),
    'storage_bucket' => Env::get('SUPABASE_STORAGE_BUCKET', 'license-uploads'),
];
