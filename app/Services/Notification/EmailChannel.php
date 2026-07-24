<?php

namespace App\Services\Notification;

class EmailChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool
    {
        // Email SMTP dispatch engine
        return @mail($recipient, $title, $message);
    }
}
