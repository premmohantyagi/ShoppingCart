<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Pagination\LengthAwarePaginator;

class CmsService
{
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Page::latest()->paginate($perPage);
    }

    public function store(array $data): Page
    {
        return Page::create($data);
    }

    public function update(Page $page, array $data): Page
    {
        $page->update($data);
        return $page->fresh();
    }

    public function delete(Page $page): bool
    {
        return $page->delete();
    }

    public function findBySlug(string $slug): Page
    {
        return Page::where('slug', $slug)->published()->firstOrFail();
    }
}
