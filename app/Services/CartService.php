<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function getCart(?int $userId, ?string $sessionId): Cart
    {
        return Cart::getOrCreate($userId, $sessionId);
    }

    public function getCartWithItems(?int $userId, ?string $sessionId): Cart
    {
        $cart = $this->getCart($userId, $sessionId);
        $cart->load('items.product.media', 'items.productVariant', 'items.vendor');
        return $cart;
    }

    public function addItem(?int $userId, ?string $sessionId, int $productId, ?int $variantId, int $quantity = 1): CartItem
    {
        return DB::transaction(function () use ($userId, $sessionId, $productId, $variantId, $quantity) {
            $cart = $this->getCart($userId, $sessionId);
            $product = Product::findOrFail($productId);
            $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

            // Validate stock
            $available = $this->inventoryService->getAvailableStock($productId, $variantId);
            if ($available < $quantity) {
                throw new \RuntimeException("Only {$available} item(s) available.");
            }

            $unitPrice = $variant ? ($variant->sale_price ?? $variant->regular_price) : ($product->sale_price ?? $product->regular_price);

            // Check if item already in cart
            $existingItem = $cart->items()
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($existingItem) {
                $newQty = $existingItem->quantity + $quantity;
                if ($available < $newQty) {
                    throw new \RuntimeException("Only {$available} item(s) available. You already have {$existingItem->quantity} in cart.");
                }
                $existingItem->update([
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $newQty,
                ]);
                return $existingItem->fresh();
            }

            return $cart->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'vendor_id' => $product->vendor_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ]);
        });
    }

    public function updateQuantity(CartItem $cartItem, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $cartItem->delete();
            return $cartItem;
        }

        $available = $this->inventoryService->getAvailableStock(
            $cartItem->product_id,
            $cartItem->product_variant_id
        );

        if ($available < $quantity) {
            throw new \RuntimeException("Only {$available} item(s) available.");
        }

        $cartItem->update([
            'quantity' => $quantity,
            'subtotal' => $cartItem->unit_price * $quantity,
        ]);

        return $cartItem->fresh();
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cartItem->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeGuestCart(?int $userId, string $sessionId): void
    {
        if (!$userId) return;

        $guestCart = Cart::active()->forSession($sessionId)->first();
        if (!$guestCart || $guestCart->items->isEmpty()) return;

        $userCart = Cart::getOrCreate($userId, null);

        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->first();

                if ($existingItem) {
                    $newQty = $existingItem->quantity + $guestItem->quantity;
                    $existingItem->update([
                        'quantity' => $newQty,
                        'subtotal' => $existingItem->unit_price * $newQty,
                    ]);
                } else {
                    $userCart->items()->create($guestItem->only([
                        'product_id', 'product_variant_id', 'vendor_id',
                        'quantity', 'unit_price', 'subtotal',
                    ]));
                }
            }

            $guestCart->items()->delete();
            $guestCart->update(['status' => 'converted']);
        });
    }

    public function validateStock(Cart $cart): array
    {
        $issues = [];

        foreach ($cart->items as $item) {
            $available = $this->inventoryService->getAvailableStock(
                $item->product_id,
                $item->product_variant_id
            );

            if ($available <= 0) {
                $issues[] = [
                    'item_id' => $item->id,
                    'product' => $item->product->name,
                    'issue' => 'out_of_stock',
                    'available' => 0,
                ];
                $item->delete();
            } elseif ($available < $item->quantity) {
                $issues[] = [
                    'item_id' => $item->id,
                    'product' => $item->product->name,
                    'issue' => 'reduced',
                    'available' => $available,
                    'was' => $item->quantity,
                ];
                $item->update([
                    'quantity' => $available,
                    'subtotal' => $item->unit_price * $available,
                ]);
            }
        }

        return $issues;
    }
}
