<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Pagination\LengthAwarePaginator;

class CouponService
{
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::latest()->paginate($perPage);
    }

    public function store(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($data);
        return $coupon->fresh();
    }

    public function delete(Coupon $coupon): bool
    {
        return $coupon->delete();
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', strtoupper($code))->first();
    }

    public function validate(string $code, float $orderTotal, ?int $userId = null): array
    {
        $coupon = $this->findByCode($code);

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Coupon not found.'];
        }

        if (!$coupon->isValid($orderTotal, $userId)) {
            return ['valid' => false, 'message' => 'Coupon is not valid for this order.'];
        }

        $discount = $coupon->calculateDiscount($orderTotal);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'message' => "Coupon applied! You save " . format_price($discount),
        ];
    }

    public function recordUsage(Coupon $coupon, int $orderId, ?int $userId): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'order_id' => $orderId,
            'user_id' => $userId,
            'used_at' => now(),
        ]);

        $coupon->increment('usage_count');
    }
}
