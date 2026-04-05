<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\PaymentSucceeded;
use App\Models\StockReservation;
use App\Models\Vendor;
use App\Notifications\PaymentSucceededNotification;
use App\Notifications\VendorNewOrderNotification;
use App\Services\InventoryService;

class HandlePaymentSucceeded
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function handle(PaymentSucceeded $event): void
    {
        $order = $event->payment->order;

        // Update order status
        if ($order->order_status === OrderStatus::Pending) {
            $order->update(['order_status' => OrderStatus::Confirmed]);
        }

        // Convert reserved stock to sold
        $reservations = StockReservation::where('order_id', $order->id)->get();
        foreach ($reservations as $reservation) {
            $this->inventoryService->confirmSold($reservation);
        }

        // Notify customer
        $order->user?->notify(new PaymentSucceededNotification($event->payment));

        // Notify vendors
        $vendorIds = $order->items->pluck('vendor_id')->unique();
        foreach ($vendorIds as $vendorId) {
            $vendor = Vendor::find($vendorId);
            $vendor?->user?->notify(new VendorNewOrderNotification($order));
        }
    }
}
