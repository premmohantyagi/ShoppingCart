<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Wishlist;
use Illuminate\View\View;

class FrontLayoutComposer
{
    public function compose(View $view): void
    {
        // Root categories for nav (lightweight query, no caching needed)
        $navCategories = Category::active()->roots()->ordered()->get(['id', 'name', 'slug']);

        // Cart count
        $cartCount = 0;
        $cart = Cart::active()
            ->when(auth()->id(), fn ($q) => $q->forUser(auth()->id()), fn ($q) => $q->forSession(session()->getId()))
            ->first();
        if ($cart) {
            $cartCount = $cart->item_count;
        }

        // Wishlist count
        $wishlistCount = 0;
        $wishlist = Wishlist::when(auth()->id(), fn ($q) => $q->where('user_id', auth()->id()), fn ($q) => $q->where('session_id', session()->getId()))
            ->first();
        if ($wishlist) {
            $wishlistCount = $wishlist->item_count;
        }

        $view->with(compact('navCategories', 'cartCount', 'wishlistCount'));
    }
}
