<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxClass extends Model
{
    use HasFactory, HasSlug;

    protected string $slugFrom = 'name';

    protected $fillable = [
        'name',
        'slug',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    // Relationships

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function activeTaxes(): HasMany
    {
        return $this->hasMany(Tax::class)->where('is_active', true);
    }

    // Helpers

    public static function getDefault(): ?static
    {
        return static::where('is_default', true)->first();
    }
}
