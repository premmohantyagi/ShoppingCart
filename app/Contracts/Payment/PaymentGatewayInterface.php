<?php

namespace App\Contracts\Payment;

interface PaymentGatewayInterface
{
    public function initiate(array $payload): array;

    public function verify(array $payload): array;

    public function refund(array $payload): array;
}
