<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Models\OrderItem;
use App\Models\Shipment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorOrderService
{
    public function getVendorOrders(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return OrderItem::with(['order.user', 'product', 'productVariant'])
            ->where('vendor_id', $vendorId)
            ->when($filters['fulfillment_status'] ?? null, fn ($q, $s) => $q->where('fulfillment_status', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$s}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function getOrderDetail(int $orderId, int $vendorId): array
    {
        $items = OrderItem::with(['product.media', 'productVariant', 'order.user', 'order.shippingAddress'])
            ->where('order_id', $orderId)
            ->where('vendor_id', $vendorId)
            ->get();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Order not found.');
        }

        $order = $items->first()->order;
        $shipment = Shipment::where('order_id', $orderId)->where('vendor_id', $vendorId)->first();

        return compact('order', 'items', 'shipment');
    }

    public function updateFulfillmentStatus(int $orderId, int $vendorId, FulfillmentStatus $status): void
    {
        OrderItem::where('order_id', $orderId)
            ->where('vendor_id', $vendorId)
            ->update(['fulfillment_status' => $status]);
    }

    public function createShipment(int $orderId, int $vendorId, array $data): Shipment
    {
        return DB::transaction(function () use ($orderId, $vendorId, $data) {
            $shipment = Shipment::updateOrCreate(
                ['order_id' => $orderId, 'vendor_id' => $vendorId],
                [
                    'carrier_name' => $data['carrier_name'],
                    'tracking_number' => $data['tracking_number'],
                    'status' => 'shipped',
                    'shipped_at' => now(),
                ]
            );

            // Update fulfillment status
            $this->updateFulfillmentStatus($orderId, $vendorId, FulfillmentStatus::Shipped);

            return $shipment;
        });
    }
}
