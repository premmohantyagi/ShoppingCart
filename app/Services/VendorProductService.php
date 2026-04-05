<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorProductService
{
    public function getVendorProducts(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['category', 'brand', 'media'])
            ->where('vendor_id', $vendorId)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function store(int $vendorId, array $data): Product
    {
        return DB::transaction(function () use ($vendorId, $data) {
            $data['vendor_id'] = $vendorId;
            $data['status'] = ProductStatus::PendingReview->value;

            $product = Product::create($data);

            if (!empty($data['tags'])) {
                $product->tags()->sync($data['tags']);
            }

            if (!empty($data['seo'])) {
                $product->seo()->create($data['seo']);
            }

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            if (array_key_exists('tags', $data)) {
                $product->tags()->sync($data['tags'] ?? []);
            }

            if (!empty($data['seo'])) {
                $product->seo()->updateOrCreate(
                    ['product_id' => $product->id],
                    $data['seo']
                );
            }

            return $product->fresh();
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->tags()->detach();
            $product->seo()->delete();
            $product->variants()->delete();
            $product->downloads()->delete();
            return $product->delete();
        });
    }
}
