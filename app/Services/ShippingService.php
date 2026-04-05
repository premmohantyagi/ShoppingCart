<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class ShippingService
{
    public function calculateShipping(float $subtotal, float $totalWeight = 0): float
    {
        $enabled = Setting::get('shipping_enabled', true);
        if (!$enabled) {
            return 0;
        }

        $freeThreshold = (float) Setting::get('free_shipping_threshold', 500);
        if ($subtotal >= $freeThreshold) {
            return 0;
        }

        $defaultFee = (float) Setting::get('default_shipping_fee', 50);

        return round($defaultFee, 2);
    }

    public function getAvailableMethods(float $subtotal): array
    {
        $methods = [];

        $freeThreshold = (float) Setting::get('free_shipping_threshold', 500);
        $defaultFee = (float) Setting::get('default_shipping_fee', 50);

        if ($subtotal >= $freeThreshold) {
            $methods[] = ['id' => 'free', 'name' => 'Free Shipping', 'cost' => 0];
        } else {
            $methods[] = ['id' => 'standard', 'name' => 'Standard Shipping', 'cost' => $defaultFee];
        }

        return $methods;
    }
}
