<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected string $slugFrom = 'title';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
        ];
    }

    // Scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published);
    }
}
