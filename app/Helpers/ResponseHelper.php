<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Send JSON Response
     */
    public static function json(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        bool $success = true,
        array $meta = []
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');

        $response = [
            'success'   => $success,
            'status'    => $statusCode,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('c')
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send Success JSON Response (200 OK)
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200, array $meta = []): void
    {
        self::json($data, $message, $statusCode, true, $meta);
    }

    /**
     * Send Created JSON Response (201 Created)
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): void
    {
        self::json($data, $message, 201, true);
    }

    /**
     * Send Error JSON Response
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 400, mixed $errors = null): void
    {
        self::json($errors, $message, $statusCode, false);
    }

    /**
     * Send Unauthorized Response (401)
     */
    public static function unauthorized(string $message = 'Unauthorized access'): void
    {
        self::json(null, $message, 401, false);
    }

    /**
     * Send Forbidden Response (403)
     */
    public static function forbidden(string $message = 'Forbidden: Access denied'): void
    {
        self::json(null, $message, 403, false);
    }

    /**
     * Send Not Found Response (404)
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(null, $message, 404, false);
    }

    /**
     * Send Internal Server Error Response (500)
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::json(null, $message, 500, false);
    }
}
