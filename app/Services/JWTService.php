<?php

namespace App\Services;

use Exception;

class JWTService
{
    private static function getSecret(): string
    {
        $config = require __DIR__ . '/../Config/jwt.php';
        return $config['secret'];
    }

    private static function getAlgo(): string
    {
        $config = require __DIR__ . '/../Config/jwt.php';
        return $config['algo'] ?? 'HS256';
    }

    /**
     * Encode payload into a signed JWT string
     */
    public static function generateToken(array $payload, int $expirySeconds = 3600): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::getAlgo()
        ];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $expirySeconds;
        $payload['iss'] = $_ENV['APP_NAME'] ?? 'Enterprise License Manager';

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validate and decode JWT string. Returns payload array or null on failure.
     */
    public static function validateToken(string $jwt): ?array
    {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            return null;
        }

        list($headerB64, $payloadB64, $signatureB64) = $tokenParts;

        $signature = self::base64UrlDecode($signatureB64);
        $expectedSignature = hash_hmac('sha256', $headerB64 . "." . $payloadB64, self::getSecret(), true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null; // Signature mismatch
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload || !isset($payload['exp'])) {
            return null;
        }

        // Check expiration
        if (time() >= $payload['exp']) {
            return null; // Token expired
        }

        return $payload;
    }

    /**
     * Helper Base64Url Encode
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Helper Base64Url Decode
     */
    public static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
