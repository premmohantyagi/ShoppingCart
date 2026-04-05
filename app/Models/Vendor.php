<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\KycStatus;
use App\Enums\VendorStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Vendor extends Model
{
    use HasSlug, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'logo',
        'description',
        'status',
        'commission_type',
        'commission_value',
        'kyc_status',
        'approved_at',
    ];

    protected $slugFrom = 'business_name';

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'commission_type' => CommissionType::class,
            'commission_value' => 'decimal:2',
            'kyc_status' => KycStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function vendorAddresses(): HasMany
    {
        return $this->hasMany(VendorAddress::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(VendorBankAccount::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(VendorSetting::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    // Scopes

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Pending);
    }

    // Helpers

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }
}
