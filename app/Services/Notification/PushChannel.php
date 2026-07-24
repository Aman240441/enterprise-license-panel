<?php

namespace App\Services\Notification;

class PushChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool
    {
        // Push notification contract (FCM/WebPush)
        return true;
    }
}
