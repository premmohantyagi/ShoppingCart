<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
    ) {
    }

    public function index(Request $request): View
    {
        $orders = $this->orderService->getPaginated(
            $request->only(['status', 'payment_status', 'search'])
        );

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'user', 'items.product', 'items.vendor', 'vendorSplits.vendor',
            'payments.transactions', 'shipments.tracks', 'returns.items',
            'cancellation', 'shippingAddress', 'billingAddress', 'coupon',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['order_status' => ['required', 'string']]);

        $status = OrderStatus::from($request->order_status);
        $this->orderService->updateStatus($order, $status);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated.');
    }

    public function verifyPayment(Order $order): RedirectResponse
    {
        $payment = $order->payments()->latest()->first();

        if (!$payment) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'No payment found.');
        }

        $this->paymentService->verifyPayment($payment);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Payment verified successfully.');
    }
}
