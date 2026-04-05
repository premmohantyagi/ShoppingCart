<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private ReviewService $reviewService,
    ) {
    }

    public function shop(Request $request): View
    {
        $products = Product::with(['category', 'brand', 'media'])
            ->published()
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->brand_id, fn ($q, $b) => $q->where('brand_id', $b))
            ->when($request->price_min, fn ($q, $p) => $q->where('regular_price', '>=', $p))
            ->when($request->price_max, fn ($q, $p) => $q->where('regular_price', '<=', $p))
            ->when($request->sort, function ($q, $sort) {
                return match ($sort) {
                    'price_asc' => $q->orderBy('regular_price', 'asc'),
                    'price_desc' => $q->orderBy('regular_price', 'desc'),
                    'newest' => $q->latest(),
                    'name_asc' => $q->orderBy('name', 'asc'),
                    default => $q->latest(),
                };
            }, fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 12));

        $categories = Category::active()->roots()->ordered()->get();
        $brands = Brand::active()->get();

        return view('front.shop', compact('products', 'categories', 'brands'));
    }

    public function category(string $slug, Request $request): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $categoryIds = collect([$category->id])
            ->merge($category->children->pluck('id'));

        $products = Product::with(['category', 'brand', 'media'])
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->when($request->brand_id, fn ($q, $b) => $q->where('brand_id', $b))
            ->when($request->price_min, fn ($q, $p) => $q->where('regular_price', '>=', $p))
            ->when($request->price_max, fn ($q, $p) => $q->where('regular_price', '<=', $p))
            ->when($request->sort, function ($q, $sort) {
                return match ($sort) {
                    'price_asc' => $q->orderBy('regular_price', 'asc'),
                    'price_desc' => $q->orderBy('regular_price', 'desc'),
                    'newest' => $q->latest(),
                    default => $q->latest(),
                };
            }, fn ($q) => $q->latest())
            ->paginate(12);

        $brands = Brand::active()->get();

        return view('front.category', compact('category', 'products', 'brands'));
    }

    public function brand(string $slug, Request $request): View
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $products = Product::with(['category', 'brand', 'media'])
            ->published()
            ->where('brand_id', $brand->id)
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->sort, function ($q, $sort) {
                return match ($sort) {
                    'price_asc' => $q->orderBy('regular_price', 'asc'),
                    'price_desc' => $q->orderBy('regular_price', 'desc'),
                    'newest' => $q->latest(),
                    default => $q->latest(),
                };
            }, fn ($q) => $q->latest())
            ->paginate(12);

        $categories = Category::active()->roots()->ordered()->get();

        return view('front.brand', compact('brand', 'products', 'categories'));
    }

    public function show(string $slug): View
    {
        $product = Product::with([
            'category.parent', 'brand', 'vendor', 'tags', 'seo',
            'variants.variantAttributes.attribute',
            'variants.variantAttributes.attributeValue',
            'media', 'relatedProducts.relatedProduct.media',
        ])->where('slug', $slug)->firstOrFail();

        // Get stock for product/variants
        $stock = [];
        if ($product->variants->isNotEmpty()) {
            foreach ($product->variants as $variant) {
                $stock[$variant->id] = $this->inventoryService->getAvailableStock($product->id, $variant->id);
            }
        } else {
            $stock['main'] = $this->inventoryService->getAvailableStock($product->id);
        }

        $relatedProducts = $product->relatedProducts
            ->map(fn ($r) => $r->relatedProduct)
            ->filter()
            ->take(8);

        // Reviews
        $reviews = $this->reviewService->getProductReviews($product->id);
        $ratingSummary = $this->reviewService->getProductRatingSummary($product->id);
        $averageRating = $ratingSummary['average'];
        $ratingDistribution = $ratingSummary['distribution'];

        return view('front.product', compact('product', 'stock', 'relatedProducts', 'reviews', 'averageRating', 'ratingDistribution'));
    }

    public function search(Request $request): View
    {
        $query = $request->get('q', '');

        $products = Product::with(['category', 'brand', 'media'])
            ->published()
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->brand_id, fn ($q, $b) => $q->where('brand_id', $b))
            ->latest()
            ->paginate(12);

        $categories = Category::active()->roots()->ordered()->get();
        $brands = Brand::active()->get();

        return view('front.search', compact('products', 'categories', 'brands', 'query'));
    }
}
