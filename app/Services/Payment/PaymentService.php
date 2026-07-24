<?php

namespace App\Services\Payment;

use App\Database\DatabaseConnection;
use App\Helpers\SecurityHelper;

class PaymentService
{
    /**
     * Process payment transaction and record in payments table
     */
    public static function processPayment(
        ?int $customerId,
        ?int $licenseId,
        ?int $resellerId,
        float $amount,
        string $currency = 'USD',
        string $gatewayName = 'stripe',
        array $metadata = []
    ): array {
        $gateway = match (strtolower($gatewayName)) {
            'razorpay' => new RazorpayGateway(),
            'paypal' => new PayPalGateway(),
            default => new StripeGateway(),
        };

        $result = $gateway->charge($amount, $currency, $metadata);

        if ($result['success']) {
            $uuid = SecurityHelper::generateUuid();
            DatabaseConnection::query(
                "INSERT INTO `payments` (`uuid`, `customer_id`, `license_id`, `reseller_id`, `gateway`, `transaction_id`, `amount`, `currency`, `status`, `metadata_json`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)",
                [$uuid, $customerId, $licenseId, $resellerId, $result['gateway'], $result['transaction_id'], $amount, $currency, json_encode($metadata)]
            );

            // If reseller payment, update reseller total sales and earnings
            if ($resellerId !== null) {
                $reseller = DatabaseConnection::fetchOne("SELECT commission_rate FROM `resellers` WHERE id = ?", [$resellerId]);
                if ($reseller) {
                    $commissionRate = (float) $reseller['commission_rate'];
                    $earnings = $amount * ($commissionRate / 100);
                    DatabaseConnection::query(
                        "UPDATE `resellers` SET total_sales = total_sales + ?, total_earnings = total_earnings + ? WHERE id = ?",
                        [$amount, $earnings, $resellerId]
                    );
                }
            }
        }

        return $result;
    }
}
