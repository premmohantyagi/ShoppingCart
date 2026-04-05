<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): CartResource
    {
        $cart = $this->cartService->getCartWithItems($request->user()->id, null);

        return new CartResource($cart);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['integer', 'min:1'],
        ]);

        try {
            $this->cartService->addItem(
                $request->user()->id,
                null,
                $request->integer('product_id'),
                $request->integer('product_variant_id') ?: null,
                $request->integer('quantity', 1),
            );

            $cart = $this->cartService->getCartWithItems($request->user()->id, null);

            return response()->json(['success' => true, 'cart' => new CartResource($cart)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'cart_item_id' => ['required', 'exists:cart_items,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $cartItem = CartItem::findOrFail($request->integer('cart_item_id'));
            $this->cartService->updateQuantity($cartItem, $request->integer('quantity'));

            $cart = $this->cartService->getCartWithItems($request->user()->id, null);

            return response()->json(['success' => true, 'cart' => new CartResource($cart)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'cart_item_id' => ['required', 'exists:cart_items,id'],
        ]);

        $cartItem = CartItem::findOrFail($request->integer('cart_item_id'));
        $this->cartService->removeItem($cartItem);

        $cart = $this->cartService->getCartWithItems($request->user()->id, null);

        return response()->json(['success' => true, 'cart' => new CartResource($cart)]);
    }
}
