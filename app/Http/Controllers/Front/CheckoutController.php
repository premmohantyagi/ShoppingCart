<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private PaymentService $paymentService,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $data = $this->checkoutService->getCheckoutData(auth()->id(), session()->getId());

        if ($data['cart']->items->isEmpty()) {
            return redirect()->route('front.cart')->with('error', 'Your cart is empty.');
        }

        $addresses = Address::where('user_id', auth()->id())->get();

        return view('front.checkout.index', array_merge($data, [
            'addresses' => $addresses,
            'payment_methods' => [
                ['id' => 'cod', 'name' => 'Cash on Delivery'],
                ['id' => 'bank_transfer', 'name' => 'Bank Transfer'],
            ],
        ]));
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['coupon_code' => ['required', 'string']]);

        $subtotal = (float) $request->get('subtotal', 0);
        $result = $this->checkoutService->applyCoupon(
            $request->coupon_code,
            $subtotal,
            auth()->id()
        );

        return response()->json($result);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'shipping_address_id' => ['required', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'exists:addresses,id'],
            'payment_method' => ['required', 'in:cod,bank_transfer'],
            'coupon_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->checkoutService->placeOrder(
                auth()->id(),
                session()->getId(),
                $request->integer('shipping_address_id'),
                $request->integer('billing_address_id') ?: null,
                $request->payment_method,
                $request->coupon_code,
                $request->notes,
            );

            // Initiate payment
            $payment = $this->paymentService->initiatePayment($order);

            // COD orders are auto-confirmed
            if ($request->payment_method === 'cod') {
                return redirect()->route('front.orders.confirmation', $order)
                    ->with('success', 'Order placed successfully!');
            }

            return redirect()->route('front.orders.confirmation', $order)
                ->with('success', 'Order placed! Please complete payment.');

        } catch (\RuntimeException $e) {
            return redirect()->route('front.checkout')
                ->with('error', $e->getMessage());
        }
    }

    public function confirmation(Order $order): View
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load('items.product', 'shippingAddress', 'payments');

        return view('front.checkout.confirmation', compact('order'));
    }
}
