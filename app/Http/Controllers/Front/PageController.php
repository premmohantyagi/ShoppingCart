<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\CmsService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private CmsService $cmsService) {}

    public function show(string $slug): View
    {
        $page = $this->cmsService->findBySlug($slug);
        return view('front.page', compact('page'));
    }
}
