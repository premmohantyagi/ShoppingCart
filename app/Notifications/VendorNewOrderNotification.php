<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorNewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendor = $notifiable->vendor;
        $items = $this->order->items->where('vendor_id', $vendor?->id);

        $message = (new MailMessage)
            ->subject('New Order Received - ' . $this->order->order_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new order: ' . $this->order->order_number . '.');

        foreach ($items as $item) {
            $message->line('- ' . $item->product_name . ' (Qty: ' . $item->quantity . ')');
        }

        return $message
            ->action('View Order', url('/vendor-panel/orders/' . $this->order->id))
            ->line('Please process this order promptly.');
    }

    public function toArray(object $notifiable): array
    {
        $vendor = $notifiable->vendor;
        $items = $this->order->items->where('vendor_id', $vendor?->id);
        $itemSummary = $items->map(fn ($item) => $item->product_name . ' x' . $item->quantity)->implode(', ');

        return [
            'type' => 'vendor_new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'items' => $itemSummary,
            'message' => 'New order ' . $this->order->order_number . ': ' . $itemSummary,
        ];
    }
}
