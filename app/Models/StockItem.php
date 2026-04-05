<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'opening_stock',
        'in_stock',
        'reserved_stock',
        'sold_stock',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'opening_stock' => 'integer',
            'in_stock' => 'integer',
            'reserved_stock' => 'integer',
            'sold_stock' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->in_stock - $this->reserved_stock);
    }

    public function isLowStock(): bool
    {
        return $this->in_stock <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->in_stock <= 0;
    }
}
