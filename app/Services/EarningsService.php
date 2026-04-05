<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderVendorSplit;
use App\Models\VendorWallet;

class EarningsService
{
    public function getEarningsSummary(int $vendorId): array
    {
        $splits = OrderVendorSplit::where('vendor_id', $vendorId);

        return [
            'total_sales' => (float) $splits->sum('subtotal'),
            'total_commission' => (float) $splits->sum('commission_amount'),
            'total_payouts' => (float) $splits->sum('payout_amount'),
            'pending_earnings' => (float) $splits->clone()->where('status', 'pending')->sum('payout_amount'),
            'wallet_balance' => (float) (VendorWallet::where('vendor_id', $vendorId)->value('balance') ?? 0),
        ];
    }

    public function getEarningsHistory(int $vendorId, int $perPage = 15)
    {
        return OrderVendorSplit::with('order')
            ->where('vendor_id', $vendorId)
            ->latest()
            ->paginate($perPage);
    }
}
