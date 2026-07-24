<?php

namespace App\Services\Payment;

class RazorpayGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, string $currency, array $metadata = []): array
    {
        return [
            'success' => true,
            'gateway' => 'razorpay',
            'transaction_id' => 'pay_rzp_' . bin2hex(random_bytes(8)),
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
