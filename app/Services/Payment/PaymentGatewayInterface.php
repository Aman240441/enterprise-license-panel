<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process checkout or subscription charge
     */
    public function charge(float $amount, string $currency, array $metadata = []): array;

    /**
     * Verify payment status with gateway API
     */
    public function verifyTransaction(string $transactionId): bool;

    /**
     * Process refund
     */
    public function refund(string $transactionId, ?float $amount = null): bool;
}
