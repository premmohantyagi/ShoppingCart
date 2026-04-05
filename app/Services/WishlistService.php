<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    public function getWishlist(?int $userId, ?string $sessionId): Wishlist
    {
        return Wishlist::getOrCreate($userId, $sessionId);
    }

    public function getWishlistWithItems(?int $userId, ?string $sessionId): Wishlist
    {
        $wishlist = $this->getWishlist($userId, $sessionId);
        $wishlist->load('items.product.media', 'items.product.category', 'items.productVariant');
        return $wishlist;
    }

    public function addItem(?int $userId, ?string $sessionId, int $productId, ?int $variantId = null): WishlistItem
    {
        $wishlist = $this->getWishlist($userId, $sessionId);

        $existing = $wishlist->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $wishlist->items()->create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'added_at' => now(),
        ]);
    }

    public function removeItem(?int $userId, ?string $sessionId, int $productId, ?int $variantId = null): void
    {
        $wishlist = $this->getWishlist($userId, $sessionId);

        $wishlist->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->delete();
    }

    public function isInWishlist(?int $userId, ?string $sessionId, int $productId, ?int $variantId = null): bool
    {
        $wishlist = Wishlist::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->first();

        if (!$wishlist) return false;

        return $wishlist->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->exists();
    }

    public function mergeGuestWishlist(?int $userId, string $sessionId): void
    {
        if (!$userId) return;

        $guestWishlist = Wishlist::where('session_id', $sessionId)->first();
        if (!$guestWishlist || $guestWishlist->items->isEmpty()) return;

        $userWishlist = Wishlist::getOrCreate($userId, null);

        DB::transaction(function () use ($guestWishlist, $userWishlist) {
            foreach ($guestWishlist->items as $item) {
                $exists = $userWishlist->items()
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->exists();

                if (!$exists) {
                    $userWishlist->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'added_at' => $item->added_at,
                    ]);
                }
            }

            $guestWishlist->items()->delete();
            $guestWishlist->delete();
        });
    }
}
