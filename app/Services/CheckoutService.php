<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
        private ShippingService $shippingService,
        private CouponService $couponService,
        private OrderService $orderService,
    ) {
    }

    public function getCheckoutData(?int $userId, ?string $sessionId): array
    {
        $cart = $this->cartService->getCartWithItems($userId, $sessionId);
        $issues = $this->cartService->validateStock($cart);

        $subtotal = (float) $cart->total;
        $shippingMethods = $this->shippingService->getAvailableMethods($subtotal);
        $shippingCost = $shippingMethods[0]['cost'] ?? 0;

        return [
            'cart' => $cart,
            'issues' => $issues,
            'subtotal' => $subtotal,
            'shipping_methods' => $shippingMethods,
            'shipping_cost' => $shippingCost,
        ];
    }

    public function applyCoupon(string $code, float $subtotal, ?int $userId): array
    {
        return $this->couponService->validate($code, $subtotal, $userId);
    }

    public function placeOrder(
        ?int $userId,
        ?string $sessionId,
        int $shippingAddressId,
        ?int $billingAddressId,
        string $paymentMethod,
        ?string $couponCode = null,
        ?string $notes = null,
    ): Order {
        $cart = $this->cartService->getCartWithItems($userId, $sessionId);

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty.');
        }

        // Validate stock one final time
        $issues = $this->cartService->validateStock($cart);
        if (!empty($issues)) {
            throw new \RuntimeException('Some items in your cart have stock issues. Please review your cart.');
        }

        $subtotal = (float) $cart->total;
        $shippingCost = $this->shippingService->calculateShipping($subtotal);

        // Apply coupon
        $discountTotal = 0;
        $couponId = null;
        if ($couponCode) {
            $result = $this->couponService->validate($couponCode, $subtotal, $userId);
            if ($result['valid']) {
                $discountTotal = $result['discount'];
                $couponId = $result['coupon']->id;
            }
        }

        $order = $this->orderService->createFromCart(
            $cart, $shippingAddressId, $billingAddressId,
            $paymentMethod, $shippingCost, $discountTotal, $couponId, $notes
        );

        // Record coupon usage
        if ($couponId) {
            $coupon = $this->couponService->findByCode($couponCode);
            $this->couponService->recordUsage($coupon, $order->id, $userId);
        }

        return $order;
    }
}
