<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function store(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $review = $this->reviewService->store($productId, auth()->id(), $request->validated());
            return response()->json(['success' => true, 'message' => 'Review submitted for moderation.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function vote(Request $request, int $reviewId): JsonResponse
    {
        $request->validate(['is_helpful' => ['required', 'boolean']]);

        $this->reviewService->vote($reviewId, auth()->id(), $request->boolean('is_helpful'));

        return response()->json(['success' => true]);
    }
}
