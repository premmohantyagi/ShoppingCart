<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasSlug;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Brand extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasStatus, Filterable, InteractsWithMedia;

    protected string $slugFrom = 'name';

    protected array $filterable = ['status'];

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'status',
    ];

    // Relationships

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Media Collections

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
