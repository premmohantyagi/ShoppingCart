<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\OrderItem;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function getProductReviews(int $productId, int $perPage = 10): LengthAwarePaginator
    {
        return Review::with('user', 'votes')
            ->where('product_id', $productId)
            ->approved()
            ->latest()
            ->paginate($perPage);
    }

    public function getProductRatingSummary(int $productId): array
    {
        $reviews = Review::where('product_id', $productId)->approved();
        $total = $reviews->count();
        $average = $total > 0 ? round((float) $reviews->avg('rating'), 1) : 0;

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = Review::where('product_id', $productId)->approved()->where('rating', $i)->count();
            $distribution[$i] = ['count' => $count, 'percentage' => $total > 0 ? round(($count / $total) * 100) : 0];
        }

        return compact('total', 'average', 'distribution');
    }

    public function store(int $productId, int $userId, array $data): Review
    {
        $isVerified = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $userId))
            ->where('product_id', $productId)
            ->exists();

        return Review::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'],
            'is_verified_purchase' => $isVerified,
            'status' => 'pending',
        ]);
    }

    public function vote(int $reviewId, int $userId, bool $isHelpful): ReviewVote
    {
        return ReviewVote::updateOrCreate(
            ['review_id' => $reviewId, 'user_id' => $userId],
            ['is_helpful' => $isHelpful]
        );
    }

    public function getPendingReviews(int $perPage = 15): LengthAwarePaginator
    {
        return Review::with('product', 'user')->pending()->latest()->paginate($perPage);
    }

    public function approve(Review $review): void
    {
        $review->update(['status' => 'approved']);
    }

    public function reject(Review $review): void
    {
        $review->update(['status' => 'rejected']);
    }
}
