<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\Payment\PaymentGatewayInterface;

class CODGateway implements PaymentGatewayInterface
{
    public function initiate(array $payload): array
    {
        return [
            'status' => 'pending',
            'message' => 'Cash on Delivery. Payment will be collected upon delivery.',
            'reference' => 'COD-' . ($payload['order_number'] ?? uniqid()),
        ];
    }

    public function verify(array $payload): array
    {
        return [
            'status' => 'paid',
            'message' => 'COD payment verified.',
            'reference' => $payload['gateway_reference'] ?? null,
        ];
    }

    public function refund(array $payload): array
    {
        return [
            'status' => 'processed',
            'message' => 'COD refund processed manually.',
            'reference' => $payload['gateway_reference'] ?? null,
        ];
    }
}
