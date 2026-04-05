<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private array $gateways = [];

    public function registerGateway(string $code, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$code] = $gateway;
    }

    public function getGateway(string $code): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$code])) {
            throw new \RuntimeException("Payment gateway '{$code}' not registered.");
        }

        return $this->gateways[$code];
    }

    public function initiatePayment(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'gateway' => $order->payment_method,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => PaymentStatus::Initiated,
            ]);

            $gateway = $this->getGateway($order->payment_method);
            $result = $gateway->initiate([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => (float) $order->grand_total,
                'currency' => $order->currency,
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'initiate',
                'request_data' => ['order_id' => $order->id],
                'response_data' => $result,
                'status' => $result['status'] ?? 'initiated',
            ]);

            if (($result['status'] ?? '') === 'paid') {
                $this->markAsPaid($payment, $result);
            }

            return $payment->fresh();
        });
    }

    public function verifyPayment(Payment $payment, array $payload = []): Payment
    {
        $gateway = $this->getGateway($payment->gateway);
        $result = $gateway->verify(array_merge($payload, [
            'payment_id' => $payment->id,
            'gateway_reference' => $payment->gateway_reference,
        ]));

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => 'verify',
            'request_data' => $payload,
            'response_data' => $result,
            'status' => $result['status'] ?? 'unknown',
        ]);

        if (($result['status'] ?? '') === 'paid') {
            $this->markAsPaid($payment, $result);
        } elseif (($result['status'] ?? '') === 'failed') {
            $this->markAsFailed($payment, $result);
        }

        return $payment->fresh();
    }

    public function processRefund(Payment $payment, float $amount, string $reason): void
    {
        $gateway = $this->getGateway($payment->gateway);
        $result = $gateway->refund([
            'payment_id' => $payment->id,
            'gateway_reference' => $payment->gateway_reference,
            'amount' => $amount,
        ]);

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => 'refund',
            'request_data' => ['amount' => $amount, 'reason' => $reason],
            'response_data' => $result,
            'status' => $result['status'] ?? 'unknown',
        ]);
    }

    private function markAsPaid(Payment $payment, array $result): void
    {
        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'gateway_reference' => $result['reference'] ?? $payment->gateway_reference,
            'raw_response' => $result,
        ]);

        $payment->order->update(['payment_status' => PaymentStatus::Paid]);

        event(new PaymentSucceeded($payment));
    }

    private function markAsFailed(Payment $payment, array $result): void
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'raw_response' => $result,
        ]);

        $payment->order->update(['payment_status' => PaymentStatus::Failed]);

        event(new PaymentFailed($payment));
    }
}
