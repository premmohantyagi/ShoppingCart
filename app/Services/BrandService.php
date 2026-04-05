<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BrandService
{
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Brand::latest()->paginate($perPage);
    }

    public function store(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);
        return $brand->fresh();
    }

    public function delete(Brand $brand): bool
    {
        if ($brand->products()->exists()) {
            throw new \RuntimeException('Cannot delete brand with products.');
        }
        return $brand->delete();
    }

    public function getForDropdown(): Collection
    {
        return Brand::active()->get(['id', 'name']);
    }
}
