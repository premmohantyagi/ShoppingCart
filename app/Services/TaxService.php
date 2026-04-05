<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tax;
use App\Models\TaxClass;
use Illuminate\Database\Eloquent\Collection;

class TaxService
{
    public function getAllClasses(): Collection
    {
        return TaxClass::with('activeTaxes')->get();
    }

    public function storeClass(array $data): TaxClass
    {
        if (!empty($data['is_default'])) {
            TaxClass::where('is_default', true)->update(['is_default' => false]);
        }
        return TaxClass::create($data);
    }

    public function updateClass(TaxClass $taxClass, array $data): TaxClass
    {
        if (!empty($data['is_default'])) {
            TaxClass::where('is_default', true)->where('id', '!=', $taxClass->id)->update(['is_default' => false]);
        }
        $taxClass->update($data);
        return $taxClass->fresh();
    }

    public function deleteClass(TaxClass $taxClass): bool
    {
        return $taxClass->delete();
    }

    public function addTax(TaxClass $taxClass, array $data): Tax
    {
        return $taxClass->taxes()->create($data);
    }

    public function updateTax(Tax $tax, array $data): Tax
    {
        $tax->update($data);
        return $tax->fresh();
    }

    public function deleteTax(Tax $tax): bool
    {
        return $tax->delete();
    }

    public function calculateTax(float $price, ?int $taxClassId = null, ?string $region = null): float
    {
        $taxEnabled = \App\Models\Setting::get('tax_enabled', true);
        if (!$taxEnabled) {
            return 0;
        }

        if (!$taxClassId) {
            $defaultClass = TaxClass::getDefault();
            if (!$defaultClass) {
                $defaultRate = (float) \App\Models\Setting::get('default_tax_rate', 18);
                return round($price * ($defaultRate / 100), 2);
            }
            $taxClassId = $defaultClass->id;
        }

        $taxes = Tax::where('tax_class_id', $taxClassId)
            ->where('is_active', true)
            ->when($region, fn ($q) => $q->where('region', $region))
            ->get();

        $totalRate = $taxes->sum('rate');
        return round($price * ($totalRate / 100), 2);
    }
}
