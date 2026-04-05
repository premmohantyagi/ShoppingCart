<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function getAll(): Collection
    {
        return Category::with('children')->roots()->ordered()->get();
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Category::with('parent')->latest()->paginate($perPage);
    }

    public function getTree(): Collection
    {
        return Category::with('children.children')->roots()->ordered()->active()->get();
    }

    public function store(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        if ($category->children()->exists()) {
            throw new \RuntimeException('Cannot delete category with subcategories.');
        }
        if ($category->products()->exists()) {
            throw new \RuntimeException('Cannot delete category with products.');
        }
        return $category->delete();
    }

    public function getForDropdown(): Collection
    {
        return Category::active()->ordered()->get(['id', 'name', 'parent_id']);
    }
}
