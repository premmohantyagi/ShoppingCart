<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'brand', 'media'])
            ->published()
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->brand_id, fn ($q, $b) => $q->where('brand_id', $b))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->sort, function ($q, $sort) {
                return match ($sort) {
                    'price_asc' => $q->orderBy('regular_price', 'asc'),
                    'price_desc' => $q->orderBy('regular_price', 'desc'),
                    'newest' => $q->latest(),
                    default => $q->latest(),
                };
            }, fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::with([
            'category',
            'brand',
            'vendor',
            'tags',
            'variants.variantAttributes.attribute',
            'variants.variantAttributes.attributeValue',
            'media',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function reviews(int $productId, ReviewService $reviewService): AnonymousResourceCollection
    {
        $reviews = $reviewService->getProductReviews($productId);

        return ReviewResource::collection($reviews);
    }
}
