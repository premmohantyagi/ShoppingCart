<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use HasFactory, HasSlug, HasStatus;

    protected string $slugFrom = 'name';

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    // Relationships

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
