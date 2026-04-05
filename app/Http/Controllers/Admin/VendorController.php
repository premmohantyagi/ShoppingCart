<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorPayout;
use App\Services\KycService;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        private KycService $kycService,
        private PayoutService $payoutService,
    ) {
    }

    public function index(Request $request): View
    {
        $vendors = Vendor::with('user', 'wallet')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->kyc_status, fn ($q, $s) => $q->where('kyc_status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('business_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15);

        return view('admin.vendors.index', compact('vendors'));
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load('user', 'documents', 'bankAccounts', 'wallet', 'addresses');

        return view('admin.vendors.show', compact('vendor'));
    }

    public function approveKyc(Vendor $vendor): RedirectResponse
    {
        $this->kycService->approveVendor($vendor);

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('success', 'Vendor KYC approved.');
    }

    public function rejectKyc(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->kycService->rejectVendor($vendor, $request->reason ?? '');

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('success', 'Vendor KYC rejected.');
    }

    public function verifyDocument(VendorDocument $document): RedirectResponse
    {
        $this->kycService->verifyDocument($document);

        return redirect()->back()->with('success', 'Document verified.');
    }

    public function rejectDocument(VendorDocument $document): RedirectResponse
    {
        $this->kycService->rejectDocument($document);

        return redirect()->back()->with('success', 'Document rejected.');
    }

    // Payouts
    public function payouts(Request $request): View
    {
        $payouts = $this->payoutService->getAllPayouts($request->only(['status', 'vendor_id']));

        return view('admin.vendors.payouts', compact('payouts'));
    }

    public function approvePayout(VendorPayout $payout): RedirectResponse
    {
        $this->payoutService->approvePayout($payout, auth()->id());

        return redirect()->route('admin.vendors.payouts')
            ->with('success', 'Payout approved and processed.');
    }

    public function rejectPayout(Request $request, VendorPayout $payout): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->payoutService->rejectPayout($payout, $request->reason);

        return redirect()->route('admin.vendors.payouts')
            ->with('success', 'Payout rejected.');
    }
}
