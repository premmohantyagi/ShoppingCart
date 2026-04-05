<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->payment->order;

        return (new MailMessage)
            ->subject('Payment Failed - ' . $order->order_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Unfortunately, your payment for order ' . $order->order_number . ' could not be processed.')
            ->line('Amount: ' . format_price($this->payment->amount))
            ->line('Please try again or use a different payment method.')
            ->action('Retry Payment', url('/orders/' . $order->id))
            ->line('If you continue to experience issues, please contact support.');
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->payment->order;

        return [
            'type' => 'payment_failed',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $this->payment->amount,
            'message' => 'Payment failed for order ' . $order->order_number . '. Please retry.',
        ];
    }
}
