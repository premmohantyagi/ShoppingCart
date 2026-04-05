<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BannerPosition;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BannerService
{
    public function getActiveByPosition(BannerPosition $position): Collection
    {
        return Banner::active()->byPosition($position)->orderBy('sort_order')->get();
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Banner::latest()->paginate($perPage);
    }

    public function store(array $data): Banner
    {
        return Banner::create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($data);
        return $banner->fresh();
    }

    public function delete(Banner $banner): bool
    {
        return $banner->delete();
    }
}
