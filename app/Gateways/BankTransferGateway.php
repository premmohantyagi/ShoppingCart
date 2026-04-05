<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\Payment\PaymentGatewayInterface;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function initiate(array $payload): array
    {
        return [
            'status' => 'pending',
            'message' => 'Please transfer the amount to our bank account. We will verify and confirm.',
            'reference' => 'BT-' . ($payload['order_number'] ?? uniqid()),
            'bank_details' => [
                'bank_name' => 'State Bank of India',
                'account_name' => 'ShoppingCart Pvt Ltd',
                'account_number' => '1234567890',
                'ifsc_code' => 'SBIN0001234',
            ],
        ];
    }

    public function verify(array $payload): array
    {
        return [
            'status' => 'paid',
            'message' => 'Bank transfer verified.',
            'reference' => $payload['gateway_reference'] ?? null,
        ];
    }

    public function refund(array $payload): array
    {
        return [
            'status' => 'processed',
            'message' => 'Refund will be transferred to your bank account.',
            'reference' => $payload['gateway_reference'] ?? null,
        ];
    }
}
