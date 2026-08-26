<?php

namespace App\Models;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    use Translatable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'cta_label' => 'array',
            'locales' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Active right now: switched on and inside its optional date window. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where(function ($q) use ($locale) {
            $q->whereNull('locales')
                ->orWhereJsonContains('locales', $locale);
        });
    }
}
