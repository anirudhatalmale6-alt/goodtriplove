<?php

namespace App\Models;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;
    use Translatable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
