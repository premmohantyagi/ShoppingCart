<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private CategoryService $categoryService,
        private BrandService $brandService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category_id', 'brand_id', 'vendor_id', 'search']);
        $products = $this->productService->getPaginated($filters);
        $categories = $this->categoryService->getForDropdown();
        $brands = $this->brandService->getForDropdown();

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    public function create(): View
    {
        $categories = $this->categoryService->getForDropdown();
        $brands = $this->brandService->getForDropdown();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->store($request->validated());

        if ($request->hasFile('thumbnail')) {
            $product->clearMediaCollection('thumbnail');
            $product->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $product->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load(['category', 'brand', 'tags', 'seo', 'variants.variantAttributes.attribute', 'variants.variantAttributes.attributeValue']);
        $categories = $this->categoryService->getForDropdown();
        $brands = $this->brandService->getForDropdown();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
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

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
