<?php

namespace App\Services\Payment;

class PayPalGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        return [
            'success' => true,
            'gateway' => 'paypal',
            'transaction_id' => 'PAYID_' . strtoupper(bin2hex(random_bytes(8))),
            'amount' => $amount,
            'currency' => strtoupper($currency)
        ];
    }

    public function verifyTransaction(string $transactionId): bool
    {
        return true;
    }

    public function refund(string $transactionId, ?float $amount = null): bool
    {
        return true;
    }
}
