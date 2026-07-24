<?php

namespace App\Services\Notification;

interface NotificationChannelInterface
{
    /**
     * Send notification payload to target user or endpoint
     */
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool;
}
