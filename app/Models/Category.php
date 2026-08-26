<?php

namespace App\Models;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    use Translatable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'search_terms' => 'array',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
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

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Keywords the collector appends to a YouTube search for this category,
     * in the language of the search.
     */
    public function searchTerms(string $locale): array
    {
        $terms = $this->search_terms ?? [];

        return $terms[$locale] ?? $terms[config('goodtriplove.default_locale')] ?? [];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
