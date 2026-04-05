<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index(): View
    {
        $cart = $this->cartService->getCartWithItems(
            auth()->id(),
            session()->getId()
        );
        $issues = $this->cartService->validateStock($cart);

        return view('front.cart', compact('cart', 'issues'));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['integer', 'min:1'],
        ]);

        try {
            $item = $this->cartService->addItem(
                auth()->id(),
                session()->getId(),
                $request->integer('product_id'),
                $request->integer('product_variant_id') ?: null,
                $request->integer('quantity', 1),
            );

            $cart = $this->cartService->getCart(auth()->id(), session()->getId());

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart.',
                'cart_count' => $cart->item_count,
                'cart_total' => $cart->total,
            ]);
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

            $cart = $this->cartService->getCart(auth()->id(), session()->getId());

            return response()->json([
                'success' => true,
                'cart_count' => $cart->item_count,
                'cart_total' => $cart->total,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate(['cart_item_id' => ['required', 'exists:cart_items,id']]);

        $cartItem = CartItem::findOrFail($request->integer('cart_item_id'));
        $this->cartService->removeItem($cartItem);

        $cart = $this->cartService->getCart(auth()->id(), session()->getId());

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $cart->item_count,
            'cart_total' => $cart->total,
        ]);
    }

    public function clear(): RedirectResponse
    {
        $cart = $this->cartService->getCart(auth()->id(), session()->getId());
        $this->cartService->clearCart($cart);

        return redirect()->route('front.cart')->with('success', 'Cart cleared.');
    }
}
