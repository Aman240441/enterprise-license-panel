<?php

namespace App\Services\Notification;

class WebhookChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $title, string $message, array $metadata = []): bool
    {
        $payload = json_encode([
            'event' => $title,
            'message' => $message,
            'timestamp' => date('c'),
            'data' => $metadata
        ]);

        $ch = curl_init($recipient);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }
}
