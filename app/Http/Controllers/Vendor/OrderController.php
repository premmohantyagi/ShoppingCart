<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Enums\FulfillmentStatus;
use App\Services\VendorOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private VendorOrderService $orderService)
    {
    }

    private function vendorId(): int
    {
        return auth()->user()->vendor->id;
    }

    public function index(Request $request): View
    {
        $orderItems = $this->orderService->getVendorOrders(
            $this->vendorId(),
            $request->only(['fulfillment_status', 'search'])
        );

        return view('vendor.orders.index', compact('orderItems'));
    }

    public function show(int $orderId): View
    {
        $data = $this->orderService->getOrderDetail($orderId, $this->vendorId());

        return view('vendor.orders.show', $data);
    }

    public function updateFulfillment(Request $request, int $orderId): RedirectResponse
    {
        $request->validate(['fulfillment_status' => ['required', 'string']]);

        $status = FulfillmentStatus::from($request->fulfillment_status);
        $this->orderService->updateFulfillmentStatus($orderId, $this->vendorId(), $status);

        return redirect()->route('vendor.orders.show', $orderId)
            ->with('success', 'Fulfillment status updated.');
    }

    public function createShipment(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'carrier_name' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:255'],
        ]);

        $this->orderService->createShipment($orderId, $this->vendorId(), $request->validated());

        return redirect()->route('vendor.orders.show', $orderId)
            ->with('success', 'Shipment created and items marked as shipped.');
    }
}
