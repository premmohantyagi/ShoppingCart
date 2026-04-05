<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorSplit;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getAdminKpis(): array
    {
        return [
            'total_sales' => (float) Order::where('payment_status', PaymentStatus::Paid)->sum('grand_total'),
            'total_orders' => Order::count(),
            'total_customers' => User::role('customer')->count(),
            'total_products' => Product::count(),
            'total_vendors' => Vendor::approved()->count(),
            'pending_orders' => Order::where('order_status', OrderStatus::Pending)->count(),
            'low_stock_count' => StockItem::whereColumn('in_stock', '<=', 'low_stock_threshold')->count(),
        ];
    }

    public function getRecentOrders(int $limit = 5)
    {
        return Order::with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getTopProducts(int $limit = 5)
    {
        return DB::table('order_items')
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(line_total) as total_revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    public function getMonthlySales(int $months = 6): array
    {
        $data = Order::where('payment_status', PaymentStatus::Paid)
            ->where('placed_at', '>=', now()->subMonths($months))
            ->selectRaw("DATE_FORMAT(placed_at, '%Y-%m') as month, SUM(grand_total) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->format('M Y');
            $values[] = (float) ($data[$key] ?? 0);
        }

        return compact('labels', 'values');
    }

    public function getVendorKpis(int $vendorId): array
    {
        return [
            'total_sales' => (float) OrderVendorSplit::where('vendor_id', $vendorId)->sum('subtotal'),
            'total_orders' => OrderItem::where('vendor_id', $vendorId)->distinct('order_id')->count('order_id'),
            'total_products' => Product::where('vendor_id', $vendorId)->count(),
            'pending_orders' => OrderItem::where('vendor_id', $vendorId)
                ->where('fulfillment_status', 'pending')->count(),
            'wallet_balance' => (float) (VendorWallet::where('vendor_id', $vendorId)->value('balance') ?? 0),
        ];
    }
}
