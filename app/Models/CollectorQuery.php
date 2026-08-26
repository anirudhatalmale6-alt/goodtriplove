<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectorQuery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
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

    public function runs(): HasMany
    {
        return $this->hasMany(CollectorRun::class);
    }

    /** Due = active and either never run or past its interval. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_run_at')
                    ->orWhereRaw('last_run_at <= DATE_SUB(NOW(), INTERVAL interval_hours HOUR)');
            })
            ->orderBy('priority')
            ->orderByRaw('last_run_at IS NOT NULL')
            ->orderBy('last_run_at');
    }
}
