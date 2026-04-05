<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\LowStockAlert;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function setStock(int $warehouseId, int $productId, ?int $variantId, int $quantity, ?int $userId = null): StockItem
    {
        return DB::transaction(function () use ($warehouseId, $productId, $variantId, $quantity, $userId) {
            $stockItem = StockItem::updateOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId, 'product_variant_id' => $variantId],
                ['opening_stock' => $quantity, 'in_stock' => $quantity]
            );
            $this->recordMovement($warehouseId, $productId, $variantId, StockMovementType::Opening, $quantity, 'Initial stock set', $userId);
            $this->checkLowStock($stockItem);
            return $stockItem;
        });
    }

    public function adjustStock(StockItem $stockItem, int $quantity, StockMovementType $type, ?string $note = null, ?int $userId = null): StockItem
    {
        return DB::transaction(function () use ($stockItem, $quantity, $type, $note, $userId) {
            $stockItem->increment('in_stock', $quantity);
            $this->recordMovement($stockItem->warehouse_id, $stockItem->product_id, $stockItem->product_variant_id, $type, $quantity, $note, $userId);
            $stockItem->refresh();
            $this->checkLowStock($stockItem);
            return $stockItem;
        });
    }

    public function reserveStock(int $productId, ?int $variantId, int $quantity, ?int $orderId = null, ?int $warehouseId = null): StockReservation
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity, $orderId, $warehouseId) {
            $stockItem = $this->findStockItem($productId, $variantId, $warehouseId);

            if ($stockItem->available_stock < $quantity) {
                throw new \RuntimeException("Insufficient stock. Available: {$stockItem->available_stock}, Requested: {$quantity}");
            }

            $stockItem->decrement('in_stock', $quantity);
            $stockItem->increment('reserved_stock', $quantity);

            $this->recordMovement($stockItem->warehouse_id, $productId, $variantId, StockMovementType::Reserve, -$quantity, "Reserved for order #{$orderId}");

            $reservationMinutes = (int) \App\Models\Setting::get('stock_reservation_minutes', 30);

            $reservation = StockReservation::create([
                'stock_item_id' => $stockItem->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'warehouse_id' => $stockItem->warehouse_id,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes($reservationMinutes),
                'created_at' => now(),
            ]);

            $stockItem->refresh();
            $this->checkLowStock($stockItem);

            return $reservation;
        });
    }

    public function releaseStock(StockReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $stockItem = $reservation->stockItem;
            $stockItem->increment('in_stock', $reservation->quantity);
            $stockItem->decrement('reserved_stock', $reservation->quantity);
            $this->recordMovement($reservation->warehouse_id, $reservation->product_id, $reservation->product_variant_id, StockMovementType::Release, $reservation->quantity, "Released from order #{$reservation->order_id}");
            $reservation->delete();
        });
    }

    public function confirmSold(StockReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $stockItem = $reservation->stockItem;
            $stockItem->decrement('reserved_stock', $reservation->quantity);
            $stockItem->increment('sold_stock', $reservation->quantity);
            $this->recordMovement($reservation->warehouse_id, $reservation->product_id, $reservation->product_variant_id, StockMovementType::Sold, -$reservation->quantity, "Sold via order #{$reservation->order_id}");
            $reservation->delete();
        });
    }

    public function processReturn(int $warehouseId, int $productId, ?int $variantId, int $quantity, ?string $note = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($warehouseId, $productId, $variantId, $quantity, $note, $userId) {
            $stockItem = $this->findStockItem($productId, $variantId, $warehouseId);
            $stockItem->increment('in_stock', $quantity);
            $stockItem->decrement('sold_stock', $quantity);
            $this->recordMovement($warehouseId, $productId, $variantId, StockMovementType::ReturnIn, $quantity, $note ?? 'Product returned', $userId);
        });
    }

    public function getAvailableStock(int $productId, ?int $variantId = null): int
    {
        return (int) StockItem::where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->selectRaw('COALESCE(SUM(in_stock - reserved_stock), 0) as available')
            ->value('available');
    }

    public function releaseExpiredReservations(): int
    {
        $expired = StockReservation::where('expires_at', '<', now())->get();
        $count = 0;
        foreach ($expired as $reservation) {
            $this->releaseStock($reservation);
            $count++;
        }
        return $count;
    }

    private function findStockItem(int $productId, ?int $variantId, ?int $warehouseId): StockItem
    {
        $query = StockItem::where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId));

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        } else {
            $query->whereHas('warehouse', fn ($q) => $q->where('is_primary', true));
        }

        $stockItem = $query->first();
        if (!$stockItem) {
            throw new \RuntimeException('Stock item not found.');
        }
        return $stockItem;
    }

    private function recordMovement(int $warehouseId, int $productId, ?int $variantId, StockMovementType $type, int $quantity, ?string $note = null, ?int $userId = null): StockMovement
    {
        return StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'movement_type' => $type,
            'quantity' => $quantity,
            'note' => $note,
            'created_by' => $userId ?? auth()->id(),
            'created_at' => now(),
        ]);
    }

    private function checkLowStock(StockItem $stockItem): void
    {
        if ($stockItem->isLowStock()) {
            LowStockAlert::firstOrCreate(
                ['product_id' => $stockItem->product_id, 'product_variant_id' => $stockItem->product_variant_id, 'warehouse_id' => $stockItem->warehouse_id, 'is_sent' => false],
                ['alert_quantity' => $stockItem->in_stock, 'created_at' => now()]
            );
        }
    }
}
