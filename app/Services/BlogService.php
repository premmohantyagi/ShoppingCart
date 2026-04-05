<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogService
{
    public function getPublishedPosts(int $perPage = 12): LengthAwarePaginator
    {
        return BlogPost::with('category', 'author', 'media')
            ->published()
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function getCategoryPosts(string $slug, int $perPage = 12): array
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();
        $posts = BlogPost::with('author', 'media')
            ->where('blog_category_id', $category->id)
            ->published()
            ->latest('published_at')
            ->paginate($perPage);

        return compact('category', 'posts');
    }

    public function getPost(string $slug): BlogPost
    {
        return BlogPost::with('category', 'author', 'media')
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();
    }

    // Admin
    public function getAllPosts(int $perPage = 15): LengthAwarePaginator
    {
        return BlogPost::with('category', 'author')->latest()->paginate($perPage);
    }

    public function getCategories(): Collection
    {
        return BlogCategory::withCount('posts')->get();
    }

    public function storePost(array $data): BlogPost
    {
        return BlogPost::create($data);
    }

    public function updatePost(BlogPost $post, array $data): BlogPost
    {
        $post->update($data);
        return $post->fresh();
    }

    public function deletePost(BlogPost $post): bool
    {
        return $post->delete();
    }

    public function storeCategory(array $data): BlogCategory
    {
        return BlogCategory::create($data);
    }

    public function deleteCategory(BlogCategory $category): bool
    {
        return $category->delete();
    }
}
