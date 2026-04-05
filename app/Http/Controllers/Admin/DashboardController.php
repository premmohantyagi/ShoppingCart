<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function index(): View
    {
        $kpis = $this->dashboardService->getAdminKpis();
        $recentOrders = $this->dashboardService->getRecentOrders();
        $topProducts = $this->dashboardService->getTopProducts();
        $monthlySales = $this->dashboardService->getMonthlySales();

        return view('admin.dashboard', compact('kpis', 'recentOrders', 'topProducts', 'monthlySales'));
    }
}
