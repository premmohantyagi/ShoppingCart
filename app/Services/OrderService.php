<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorSplit;
use App\Models\Cart;
use App\Models\Vendor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
        private TaxService $taxService,
    ) {
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['user', 'items'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('order_status', $s))
            ->when($filters['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->when($filters['user_id'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate($perPage);
    }

    public function createFromCart(
        Cart $cart,
        int $shippingAddressId,
        ?int $billingAddressId,
        string $paymentMethod,
        float $shippingCost,
        float $discountTotal = 0,
        ?int $couponId = null,
        ?string $notes = null,
    ): Order {
        return DB::transaction(function () use ($cart, $shippingAddressId, $billingAddressId, $paymentMethod, $shippingCost, $discountTotal, $couponId, $notes) {
            $cart->load('items.product', 'items.productVariant', 'items.vendor');

            // Calculate totals
            $subtotal = $cart->items->sum('subtotal');
            $taxTotal = $this->taxService->calculateTax((float) $subtotal);
            $grandTotal = $subtotal - $discountTotal + $taxTotal + $shippingCost;

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $cart->user_id,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId ?? $shippingAddressId,
                'coupon_id' => $couponId,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingCost,
                'grand_total' => max(0, $grandTotal),
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending->value,
                'order_status' => OrderStatus::Pending->value,
                'notes' => $notes,
                'placed_at' => now(),
            ]);

            // Create order items and reserve stock
            $vendorTotals = [];
            foreach ($cart->items as $cartItem) {
                $orderItem = $order->items()->create([
                    'vendor_id' => $cartItem->vendor_id,
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'sku' => $cartItem->productVariant?->sku ?? $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'line_total' => $cartItem->subtotal,
                    'fulfillment_status' => FulfillmentStatus::Pending->value,
                ]);

                // Reserve stock
                $this->inventoryService->reserveStock(
                    $cartItem->product_id,
                    $cartItem->product_variant_id,
                    $cartItem->quantity,
                    $order->id,
                );

                // Accumulate vendor totals
                $vid = $cartItem->vendor_id;
                if (!isset($vendorTotals[$vid])) {
                    $vendorTotals[$vid] = 0;
                }
                $vendorTotals[$vid] += (float) $cartItem->subtotal;
            }

            // Create vendor splits
            foreach ($vendorTotals as $vendorId => $vendorSubtotal) {
                $vendor = Vendor::find($vendorId);
                $commissionType = $vendor->commission_type ?? 'percentage';
                $commissionValue = (float) ($vendor->commission_value ?? 10);

                $commission = $commissionType === 'percentage'
                    ? round($vendorSubtotal * ($commissionValue / 100), 2)
                    : $commissionValue;

                $order->vendorSplits()->create([
                    'vendor_id' => $vendorId,
                    'subtotal' => $vendorSubtotal,
                    'tax_total' => 0,
                    'discount_total' => 0,
                    'shipping_total' => 0,
                    'commission_amount' => $commission,
                    'payout_amount' => $vendorSubtotal - $commission,
                    'commission_type' => $commissionType,
                    'status' => 'pending',
                ]);
            }

            // Mark cart as converted
            $cart->update(['status' => 'converted']);

            return $order->load('items', 'vendorSplits');
        });
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['order_status' => $status]);
        return $order->fresh();
    }

    public function updatePaymentStatus(Order $order, PaymentStatus $status): Order
    {
        $order->update(['payment_status' => $status]);
        return $order->fresh();
    }

    public function cancelOrder(Order $order, int $userId, string $reason): void
    {
        DB::transaction(function () use ($order, $userId, $reason) {
            $order->update(['order_status' => OrderStatus::Cancelled]);

            $order->cancellation()->create([
                'requested_by' => $userId,
                'reason' => $reason,
                'status' => 'approved',
                'cancelled_at' => now(),
            ]);

            // Release reserved stock
            $reservations = \App\Models\StockReservation::where('order_id', $order->id)->get();
            foreach ($reservations as $reservation) {
                $this->inventoryService->releaseStock($reservation);
            }
        });
    }

    public function getOrderForUser(int $orderId, int $userId): ?Order
    {
        return Order::with(['items.product.media', 'items.vendor', 'payments', 'shipments', 'shippingAddress'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();
    }
}
