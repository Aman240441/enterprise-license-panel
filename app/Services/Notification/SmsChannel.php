<?php

namespace App\Services\Notification;

class SmsChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool
    {
        // SMS Gateway dispatch contract
        return true;
    }
}
