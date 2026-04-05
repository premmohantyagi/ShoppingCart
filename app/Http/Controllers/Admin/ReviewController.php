<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function index(): View
    {
        $reviews = $this->reviewService->getPendingReviews();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->reviewService->approve($review);
        return redirect()->route('admin.reviews.index')->with('success', 'Review approved.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $this->reviewService->reject($review);
        return redirect()->route('admin.reviews.index')->with('success', 'Review rejected.');
    }
}
