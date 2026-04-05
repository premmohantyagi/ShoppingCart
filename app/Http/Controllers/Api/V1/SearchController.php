<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::published()
            ->where('name', 'like', "%{$query}%")
            ->limit(6)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($p) => ['type' => 'product', 'name' => $p->name, 'url' => route('front.product', $p->slug)]);

        $categories = Category::active()
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => ['type' => 'category', 'name' => $c->name, 'url' => route('front.category', $c->slug)]);

        $brands = Brand::active()
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($b) => ['type' => 'brand', 'name' => $b->name, 'url' => route('front.brand', $b->slug)]);

        return response()->json($products->merge($categories)->merge($brands)->values());
    }
}
