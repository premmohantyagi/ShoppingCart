<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\VendorProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private VendorProductService $productService)
    {
    }

    private function vendorId(): int
    {
        return auth()->user()->vendor->id;
    }

    public function index(Request $request): View
    {
        $products = $this->productService->getVendorProducts(
            $this->vendorId(),
            $request->only(['status', 'search'])
        );

        return view('vendor.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::active()->ordered()->get(['id', 'name', 'parent_id']);
        $brands = Brand::active()->get(['id', 'name']);

        return view('vendor.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', 'string'],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:5120'],
        ]);

        $product = $this->productService->store($this->vendorId(), $request->validated());

        if ($request->hasFile('thumbnail')) {
            $product->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $product->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product created and sent for review.');
    }

    public function edit(Product $product): View
    {
        if ($product->vendor_id !== $this->vendorId()) {
            abort(403);
        }

        $product->load(['category', 'brand', 'tags', 'seo', 'media']);
        $categories = Category::active()->ordered()->get(['id', 'name', 'parent_id']);
        $brands = Brand::active()->get(['id', 'name']);

        return view('vendor.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if ($product->vendor_id !== $this->vendorId()) {
            abort(403);
        }

        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:5120'],
        ]);

        $this->productService->update($product, $request->validated());

        if ($request->hasFile('thumbnail')) {
            $product->clearMediaCollection('thumbnail');
            $product->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $product->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->vendor_id !== $this->vendorId()) {
            abort(403);
        }

        $this->productService->delete($product);

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product deleted.');
    }
}
