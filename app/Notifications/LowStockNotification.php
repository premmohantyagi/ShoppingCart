<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StockItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public StockItem $stockItem) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->stockItem->product?->name ?? 'Unknown Product';

        return (new MailMessage)
            ->subject('Low Stock Alert - ' . $productName)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('The stock for "' . $productName . '" is running low.')
            ->line('Current stock: ' . $this->stockItem->in_stock)
            ->line('Low stock threshold: ' . $this->stockItem->low_stock_threshold)
            ->action('Manage Inventory', url('/vendor-panel/inventory'))
            ->line('Please restock this product to avoid missing sales.');
    }

    public function toArray(object $notifiable): array
    {
        $productName = $this->stockItem->product?->name ?? 'Unknown Product';

        return [
            'type' => 'low_stock',
            'stock_item_id' => $this->stockItem->id,
            'product_id' => $this->stockItem->product_id,
            'product_name' => $productName,
            'current_stock' => $this->stockItem->in_stock,
            'threshold' => $this->stockItem->low_stock_threshold,
            'message' => 'Low stock alert: "' . $productName . '" has ' . $this->stockItem->in_stock . ' units remaining (threshold: ' . $this->stockItem->low_stock_threshold . ').',
        ];
    }
}
