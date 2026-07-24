<?php

namespace App\Services\Notification;

class WhatsAppChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool
    {
        // WhatsApp Business API contract
        return true;
    }
}
