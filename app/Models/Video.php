<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'metrics_updated_at' => 'datetime',
            'previous_metrics_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'classified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'scored_at' => 'datetime',
            'classification' => 'array',
            'embeddable' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_tv_eligible' => 'boolean',
            'popularity_score' => 'float',
            'trending_score' => 'float',
            'relevance_score' => 'float',
            'quality_score' => 'float',
            'classification_confidence' => 'float',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** The saved search that imported this video, when it came from the collector. */
    public function collectorQuery(): BelongsTo
    {
        return $this->belongsTo(CollectorQuery::class, 'collector_query_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class)
            ->withPivot(['match_score', 'match_reason', 'is_primary', 'confirmed'])
            ->withTimestamps();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes — these back the "most viewed / popular / trending / relevant /
     | recent" sections the platform shows for every place.
     * ------------------------------------------------------------------- */

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where('is_available', true);
    }

    public function scopeMostViewed(Builder $query): Builder
    {
        return $query->orderByDesc('view_count');
    }

    public function scopeMostPopular(Builder $query): Builder
    {
        return $query->orderByDesc('popularity_score')->orderByDesc('view_count');
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->orderByDesc('trending_score')->orderByDesc('view_count');
    }

    public function scopeMostRelevant(Builder $query): Builder
    {
        return $query->orderByDesc('relevance_score')->orderByDesc('popularity_score');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function scopeInContext(Builder $query, ?int $countryId, ?int $cityId, ?int $categoryId): Builder
    {
        return $query
            ->when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($categoryId, fn ($q) => $q->where(function ($sub) use ($categoryId) {
                $sub->where('category_id', $categoryId)
                    ->orWhere('subcategory_id', $categoryId);
            }));
    }

    /* ---------------------------------------------------------------------
     | Presentation
     * ------------------------------------------------------------------- */

    public function thumbnail(): string
    {
        if ($this->thumbnail_hq_url) {
            return $this->thumbnail_hq_url;
        }

        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        return sprintf(
            '%s/vi/%s/hqdefault.jpg',
            rtrim(config('goodtriplove.player.thumbnail_domain'), '/'),
            $this->provider_video_id
        );
    }

    /** Privacy-enhanced embed URL. Built only when the visitor asks to play. */
    public function embedUrl(array $params = []): string
    {
        $params = array_merge([
            'autoplay' => 1,
            'rel' => 0,
            'modestbranding' => 1,
            'playsinline' => 1,
        ], $params);

        return sprintf(
            '%s/embed/%s?%s',
            rtrim(config('goodtriplove.player.privacy_domain'), '/'),
            $this->provider_video_id,
            http_build_query($params)
        );
    }

    /** Demo placeholder rows carry no real provider id, so they never play. */
    public function isPlayable(): bool
    {
        return $this->provider === 'youtube' && $this->embeddable;
    }

    public function watchUrl(): string
    {
        return 'https://www.youtube.com/watch?v='.$this->provider_video_id;
    }

    public function durationForHumans(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%d:%02d', $minutes, $seconds);
    }

    public function primaryPlace(): ?Place
    {
        return $this->places->firstWhere('pivot.is_primary', true) ?? $this->places->first();
    }
}
