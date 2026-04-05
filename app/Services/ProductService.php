<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['category', 'brand', 'vendor'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when($filters['brand_id'] ?? null, fn ($q, $b) => $q->where('brand_id', $b))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            if (!empty($data['tags'])) {
                $product->tags()->sync($data['tags']);
            }

            if (!empty($data['seo'])) {
                $product->seo()->create($data['seo']);
            }

            return $product->load(['category', 'brand', 'tags', 'seo']);
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

            return $product->fresh(['category', 'brand', 'tags', 'seo']);
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->tags()->detach();
            $product->seo()->delete();
            $product->variants()->delete();
            $product->downloads()->delete();
            $product->bundledProducts()->delete();
            $product->relatedProducts()->delete();
            return $product->delete();
        });
    }

    public function publish(Product $product): Product
    {
        $product->update([
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);
        return $product->fresh();
    }

    public function unpublish(Product $product): Product
    {
        $product->update(['status' => ProductStatus::Inactive]);
        return $product->fresh();
    }

    public function addVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $variant = $product->variants()->create([
                'sku' => $data['sku'],
                'regular_price' => $data['regular_price'],
                'sale_price' => $data['sale_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'weight' => $data['weight'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (!empty($data['attributes'])) {
                foreach ($data['attributes'] as $attrId => $valueId) {
                    $variant->variantAttributes()->create([
                        'attribute_id' => $attrId,
                        'attribute_value_id' => $valueId,
                    ]);
                }
            }

            return $variant->load('variantAttributes.attribute', 'variantAttributes.attributeValue');
        });
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            $variant->update($data);

            if (!empty($data['attributes'])) {
                $variant->variantAttributes()->delete();
                foreach ($data['attributes'] as $attrId => $valueId) {
                    $variant->variantAttributes()->create([
                        'attribute_id' => $attrId,
                        'attribute_value_id' => $valueId,
                    ]);
                }
            }

            return $variant->fresh('variantAttributes.attribute', 'variantAttributes.attributeValue');
        });
    }

    public function deleteVariant(ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($variant) {
            $variant->variantAttributes()->delete();
            return $variant->delete();
        });
    }

    public function syncBundledProducts(Product $product, array $items): void
    {
        $product->bundledProducts()->delete();
        foreach ($items as $index => $item) {
            $product->bundledProducts()->create([
                'bundled_product_id' => $item['product_id'],
                'quantity' => $item['quantity'] ?? 1,
                'sort_order' => $index,
            ]);
        }
    }

    public function syncRelatedProducts(Product $product, array $relatedIds, string $type = 'related'): void
    {
        $product->relatedProducts()->where('relationship_type', $type)->delete();
        foreach ($relatedIds as $relatedId) {
            $product->relatedProducts()->create([
                'related_product_id' => $relatedId,
                'relationship_type' => $type,
            ]);
        }
    }
}
