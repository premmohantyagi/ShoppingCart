<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function index(): View
    {
        $orders = Order::with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('front.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product.media', 'items.vendor', 'payments', 'shipments.tracks', 'shippingAddress', 'billingAddress', 'coupon']);

        return view('front.orders.show', compact('order'));
    }

    public function downloadInvoice(Order $order, InvoiceService $invoiceService)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice = $invoiceService->getOrGenerate($order);
        return $invoiceService->downloadPdf($invoice);
    }
}
