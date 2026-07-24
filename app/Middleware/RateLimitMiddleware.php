<?php

namespace App\Middleware;

use App\Helpers\ResponseHelper;

class RateLimitMiddleware
{
    private static int $limit = 120; // Default requests per minute
    private static int $window = 60;  // 60 seconds

    /**
     * Enforce Rate Limiting per IP address
     */
    public static function handle(int $maxRequests = 120): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $storageDir = sys_get_temp_dir() . '/elms_ratelimit';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        $file = $storageDir . '/' . md5($ip) . '.json';
        $now = time();
        $data = ['timestamp' => $now, 'requests' => 0];

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['timestamp'], $decoded['requests'])) {
                    if ($now - $decoded['timestamp'] < self::$window) {
                        $data = $decoded;
                    }
                }
            }
        }

        $data['requests']++;
        @file_put_contents($file, json_encode($data));

        header("X-RateLimit-Limit: {$maxRequests}");
        header("X-RateLimit-Remaining: " . max(0, $maxRequests - $data['requests']));

        if ($data['requests'] > $maxRequests) {
            header("Retry-After: 60");
            ResponseHelper::json(null, "Rate limit exceeded. Too many requests. Please wait a minute before retrying.", 429, false);
        }
    }
}
