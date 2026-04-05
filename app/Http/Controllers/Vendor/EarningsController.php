<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\EarningsService;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __construct(private EarningsService $earningsService)
    {
    }

    public function index(): View
    {
        $vendorId = auth()->user()->vendor->id;
        $summary = $this->earningsService->getEarningsSummary($vendorId);
        $history = $this->earningsService->getEarningsHistory($vendorId);

        return view('vendor.earnings.index', compact('summary', 'history'));
    }
}
