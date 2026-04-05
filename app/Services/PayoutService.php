<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\VendorWallet;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function getVendorPayouts(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return VendorPayout::forVendor($vendorId)->latest()->paginate($perPage);
    }

    public function getAllPayouts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return VendorPayout::with('vendor', 'bankAccount', 'approvedBy')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->latest()
            ->paginate($perPage);
    }

    public function requestPayout(int $vendorId, float $amount): VendorPayout
    {
        $wallet = VendorWallet::where('vendor_id', $vendorId)->firstOrFail();

        if ($wallet->balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        $vendor = Vendor::findOrFail($vendorId);
        $bankAccount = $vendor->bankAccounts()->where('is_primary', true)->first();

        if (!$bankAccount) {
            throw new \RuntimeException('No primary bank account set.');
        }

        return VendorPayout::create([
            'vendor_id' => $vendorId,
            'amount' => $amount,
            'status' => 'pending',
            'bank_account_id' => $bankAccount->id,
            'requested_at' => now(),
        ]);
    }

    public function approvePayout(VendorPayout $payout, int $adminId): void
    {
        DB::transaction(function () use ($payout, $adminId) {
            $payout->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            // Deduct from wallet
            $wallet = VendorWallet::where('vendor_id', $payout->vendor_id)->firstOrFail();
            $wallet->decrement('balance', $payout->amount);

            // Mark as processed (in real app, this would be a background job)
            $payout->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        });
    }

    public function rejectPayout(VendorPayout $payout, string $reason): void
    {
        $payout->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
