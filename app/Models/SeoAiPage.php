<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoAiPage extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quality_details' => 'array',
            'generation_context' => 'array',
            'indexable' => 'boolean',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function translations(): HasMany { return $this->hasMany(SeoAiPageTranslation::class); }

    public function translation(?string $locale = null): ?SeoAiPageTranslation
    {
        $locale ??= app()->getLocale();
        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('goodtriplove.default_locale'));
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('indexable', true);
    }
}
