<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

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
        $vendor = auth()->user()->vendor;
        $kpis = $this->dashboardService->getVendorKpis($vendor->id);

        return view('vendor.dashboard', compact('kpis'));
    }
}
