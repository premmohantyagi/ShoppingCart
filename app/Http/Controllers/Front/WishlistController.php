<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlistService)
    {
    }

    public function index(): View
    {
        $wishlist = $this->wishlistService->getWishlistWithItems(
            auth()->id(),
            session()->getId()
        );

        return view('front.wishlist', compact('wishlist'));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $this->wishlistService->addItem(
            auth()->id(),
            session()->getId(),
            $request->integer('product_id'),
            $request->integer('product_variant_id') ?: null,
        );

        $wishlist = $this->wishlistService->getWishlist(auth()->id(), session()->getId());

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist.',
            'wishlist_count' => $wishlist->item_count,
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $this->wishlistService->removeItem(
            auth()->id(),
            session()->getId(),
            $request->integer('product_id'),
            $request->integer('product_variant_id') ?: null,
        );

        $wishlist = $this->wishlistService->getWishlist(auth()->id(), session()->getId());

        return response()->json([
            'success' => true,
            'message' => 'Removed from wishlist.',
            'wishlist_count' => $wishlist->item_count,
        ]);
    }
}
