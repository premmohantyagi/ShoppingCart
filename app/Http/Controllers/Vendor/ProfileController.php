<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
use App\Services\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private KycService $kycService)
    {
    }

    public function show(): View
    {
        $vendor = auth()->user()->vendor->load('documents', 'bankAccounts', 'wallet', 'addresses');

        return view('vendor.profile.show', compact('vendor'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        auth()->user()->vendor->update($request->only('business_name', 'description'));

        return redirect()->route('vendor.profile')
            ->with('success', 'Profile updated.');
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $request->validate([
            'document_type' => ['required', 'string', 'in:aadhar,pan,gst,business_license,address_proof'],
            'document' => ['required', 'file', 'max:5120'],
        ]);

        $path = $request->file('document')->store('vendor-documents/' . auth()->user()->vendor->id, 'public');

        $this->kycService->uploadDocument(
            auth()->user()->vendor->id,
            $request->document_type,
            $path
        );

        return redirect()->route('vendor.profile')
            ->with('success', 'Document uploaded.');
    }

    public function deleteDocument(VendorDocument $document): RedirectResponse
    {
        if ($document->vendor_id !== auth()->user()->vendor->id) {
            abort(403);
        }

        $this->kycService->deleteDocument($document);

        return redirect()->route('vendor.profile')
            ->with('success', 'Document removed.');
    }

    public function submitKyc(): RedirectResponse
    {
        $this->kycService->submitForReview(auth()->user()->vendor);

        return redirect()->route('vendor.profile')
            ->with('success', 'KYC submitted for review.');
    }

    public function storeBankAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'is_primary' => ['boolean'],
        ]);

        $vendorId = auth()->user()->vendor->id;

        if ($request->boolean('is_primary')) {
            VendorBankAccount::where('vendor_id', $vendorId)->update(['is_primary' => false]);
        }

        VendorBankAccount::create(array_merge(
            $request->validated(),
            ['vendor_id' => $vendorId]
        ));

        return redirect()->route('vendor.profile')
            ->with('success', 'Bank account added.');
    }

    public function deleteBankAccount(VendorBankAccount $bankAccount): RedirectResponse
    {
        if ($bankAccount->vendor_id !== auth()->user()->vendor->id) {
            abort(403);
        }

        $bankAccount->delete();

        return redirect()->route('vendor.profile')
            ->with('success', 'Bank account removed.');
    }
}
