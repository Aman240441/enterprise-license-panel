<?php

namespace App\Services\Notification;

use App\Database\DatabaseConnection;

class NotificationService
{
    /**
     * Dispatch notification across chosen channels and record in notifications database table
     */
    public static function notify(
        int $userId,
        string $title,
        string $message,
        string $channel = 'in_app',
        ?string $recipient = null,
        array $metadata = []
    ): void {
        // Record in database
        DatabaseConnection::query(
            "INSERT INTO `notifications` (`user_id`, `type`, `channel`, `title`, `message`, `metadata_json`)
             VALUES (?, 'system_alert', ?, ?, ?, ?)",
            [$userId, $channel, $title, $message, json_encode($metadata)]
        );

        if ($recipient !== null && $channel !== 'in_app') {
            $driver = match ($channel) {
                'email' => new EmailChannel(),
                'sms' => new SmsChannel(),
                'whatsapp' => new WhatsAppChannel(),
                'webhook' => new WebhookChannel(),
                'push' => new PushChannel(),
                default => null,
            };

            if ($driver !== null) {
                $driver->send($recipient, $title, $message, $metadata);
            }
        }
    }
}
