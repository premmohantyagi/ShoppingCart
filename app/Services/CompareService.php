<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompareItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class CompareService
{
    private const MAX_ITEMS = 4;

    public function getItems(?int $userId, ?string $sessionId): Collection
    {
        return CompareItem::with('product.media', 'product.category', 'product.brand')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->get();
    }

    public function addItem(?int $userId, ?string $sessionId, int $productId): CompareItem
    {
        $existing = CompareItem::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $count = CompareItem::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->count();

        if ($count >= self::MAX_ITEMS) {
            throw new \RuntimeException('You can compare up to ' . self::MAX_ITEMS . ' products.');
        }

        return CompareItem::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'product_id' => $productId,
            'added_at' => now(),
        ]);
    }

    public function removeItem(?int $userId, ?string $sessionId, int $productId): void
    {
        CompareItem::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $productId)
            ->delete();
    }

    public function clearAll(?int $userId, ?string $sessionId): void
    {
        CompareItem::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->delete();
    }

    public function getCount(?int $userId, ?string $sessionId): int
    {
        return CompareItem::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->count();
    }
}
