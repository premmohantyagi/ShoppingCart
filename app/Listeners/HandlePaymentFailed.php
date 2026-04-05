<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\PaymentFailed;
use App\Models\StockReservation;
use App\Notifications\PaymentFailedNotification;
use App\Services\InventoryService;

class HandlePaymentFailed
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function handle(PaymentFailed $event): void
    {
        $order = $event->payment->order;

        $order->update(['order_status' => OrderStatus::Failed]);

        // Release reserved stock
        $reservations = StockReservation::where('order_id', $order->id)->get();
        foreach ($reservations as $reservation) {
            $this->inventoryService->releaseStock($reservation);
        }

        // Notify customer
        $order->user?->notify(new PaymentFailedNotification($event->payment));
    }
}
