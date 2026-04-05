<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorSplit;
use App\Models\Product;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function salesReport(array $filters = []): array
    {
        $query = Order::where('payment_status', PaymentStatus::Paid)
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('placed_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('placed_at', '<=', $d));

        return [
            'total_orders' => $query->count(),
            'total_revenue' => (float) $query->sum('grand_total'),
            'total_tax' => (float) $query->sum('tax_total'),
            'total_shipping' => (float) $query->sum('shipping_total'),
            'total_discount' => (float) $query->sum('discount_total'),
            'orders' => $query->clone()->with('user')->latest('placed_at')->paginate(20),
        ];
    }

    public function productReport(array $filters = []): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('orders.placed_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('orders.placed_at', '<=', $d));

        $products = $query->clone()
            ->select('order_items.product_id', 'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.line_total) as total_revenue'))
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->paginate(20);

        return ['products' => $products];
    }

    public function vendorReport(array $filters = []): array
    {
        $vendors = OrderVendorSplit::with('vendor')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereHas('order', fn ($oq) => $oq->where('placed_at', '>=', $d)))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereHas('order', fn ($oq) => $oq->where('placed_at', '<=', $d)))
            ->select('vendor_id',
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('SUM(payout_amount) as total_payout'),
                DB::raw('COUNT(*) as order_count'))
            ->groupBy('vendor_id')
            ->orderByDesc('total_sales')
            ->paginate(20);

        return ['vendors' => $vendors];
    }

    public function stockReport(): array
    {
        $lowStock = StockItem::with('product', 'productVariant', 'warehouse')
            ->whereColumn('in_stock', '<=', 'low_stock_threshold')
            ->orderBy('in_stock')
            ->paginate(20);

        $outOfStock = StockItem::with('product', 'productVariant', 'warehouse')
            ->where('in_stock', '<=', 0)
            ->count();

        return compact('lowStock', 'outOfStock');
    }

    public function getSalesExportData(array $filters = []): array
    {
        $orders = Order::where('payment_status', PaymentStatus::Paid)
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('placed_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('placed_at', '<=', $d))
            ->latest('placed_at')
            ->get();

        $rows = [['Order #', 'Customer', 'Date', 'Subtotal', 'Discount', 'Tax', 'Shipping', 'Grand Total', 'Status']];

        foreach ($orders as $order) {
            $rows[] = [
                $order->order_number,
                $order->user?->name ?? $order->guest_email ?? 'Guest',
                $order->placed_at?->format('Y-m-d'),
                (string) $order->subtotal,
                (string) $order->discount_total,
                (string) $order->tax_total,
                (string) $order->shipping_total,
                (string) $order->grand_total,
                $order->order_status->value ?? $order->order_status,
            ];
        }

        return $rows;
    }
}
