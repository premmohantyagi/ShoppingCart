<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('children')->active()->roots()->ordered()->get();
        $featuredProducts = Product::with(['category', 'media'])
            ->published()
            ->featured()
            ->latest()
            ->limit(10)
            ->get();
        $newArrivals = Product::with(['category', 'media'])
            ->published()
            ->latest()
            ->limit(10)
            ->get();
        $heroBanners = Banner::active()->byPosition(BannerPosition::Hero)->get();

        return view('front.home', compact('categories', 'featuredProducts', 'newArrivals', 'heroBanners'));
    }
}
