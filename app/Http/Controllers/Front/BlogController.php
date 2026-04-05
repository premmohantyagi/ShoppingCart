<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Services\BlogService;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private BlogService $blogService) {}

    public function index(): View
    {
        $posts = $this->blogService->getPublishedPosts();
        $categories = $this->blogService->getCategories();
        return view('front.blog.index', compact('posts', 'categories'));
    }

    public function category(string $slug): View
    {
        $data = $this->blogService->getCategoryPosts($slug);
        $categories = $this->blogService->getCategories();
        return view('front.blog.category', array_merge($data, compact('categories')));
    }

    public function show(string $slug): View
    {
        $post = $this->blogService->getPost($slug);
        return view('front.blog.show', compact('post'));
    }
}
