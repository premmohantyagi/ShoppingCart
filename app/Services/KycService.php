<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KycStatus;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\VendorDocument;

class KycService
{
    public function uploadDocument(int $vendorId, string $documentType, string $filePath): VendorDocument
    {
        return VendorDocument::create([
            'vendor_id' => $vendorId,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'status' => 'pending',
        ]);
    }

    public function deleteDocument(VendorDocument $document): bool
    {
        return $document->delete();
    }

    public function submitForReview(Vendor $vendor): void
    {
        $vendor->update(['kyc_status' => KycStatus::Submitted]);
    }

    public function approveVendor(Vendor $vendor): void
    {
        $vendor->update([
            'kyc_status' => KycStatus::Approved,
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejectVendor(Vendor $vendor, string $reason = ''): void
    {
        $vendor->update([
            'kyc_status' => KycStatus::Rejected,
            'status' => VendorStatus::Rejected,
        ]);

        // Mark all pending documents as rejected
        $vendor->documents()->where('status', 'pending')->update(['status' => 'rejected']);
    }

    public function verifyDocument(VendorDocument $document): void
    {
        $document->update(['status' => 'verified']);
    }

    public function rejectDocument(VendorDocument $document): void
    {
        $document->update(['status' => 'rejected']);
    }

    public function getPendingVendors()
    {
        return Vendor::with('user', 'documents')
            ->where('kyc_status', KycStatus::Submitted)
            ->latest()
            ->paginate(15);
    }
}
