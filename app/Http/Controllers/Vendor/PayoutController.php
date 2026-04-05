<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payoutService)
    {
    }

    public function index(): View
    {
        $vendorId = auth()->user()->vendor->id;
        $payouts = $this->payoutService->getVendorPayouts($vendorId);
        $walletBalance = auth()->user()->vendor->wallet?->balance ?? 0;

        return view('vendor.payouts.index', compact('payouts', 'walletBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:1']]);

        try {
            $this->payoutService->requestPayout(
                auth()->user()->vendor->id,
                (float) $request->amount
            );

            return redirect()->route('vendor.payouts.index')
                ->with('success', 'Payout request submitted.');
        } catch (\RuntimeException $e) {
            return redirect()->route('vendor.payouts.index')
                ->with('error', $e->getMessage());
        }
    }
}
