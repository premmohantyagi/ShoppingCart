<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
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
            ->subject('Payment Confirmed - ' . $order->order_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment for order ' . $order->order_number . ' has been confirmed.')
            ->line('Amount: ' . format_price($this->payment->amount))
            ->action('View Order', url('/orders/' . $order->id))
            ->line('Thank you for your purchase!');
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->payment->order;

        return [
            'type' => 'payment_succeeded',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $this->payment->amount,
            'message' => 'Payment of ' . format_price($this->payment->amount) . ' confirmed for order ' . $order->order_number . '.',
        ];
    }
}
