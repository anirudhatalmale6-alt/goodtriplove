<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the ranked video sections used across the site:
 * most viewed · most popular · trending · most relevant · recent.
 */
class VideoFeedService
{
    public const SECTIONS = ['popular', 'most_viewed', 'trending', 'relevant', 'recent'];

    /**
     * @return array<string, EloquentCollection<int, Video>>
     */
    public function sectionsFor(array $context = [], int $perSection = 8): array
    {
        $sections = [];

        foreach (self::SECTIONS as $section) {
            $videos = $this->section($section, $context, $perSection);

            if ($videos->isNotEmpty()) {
                $sections[$section] = $videos;
            }
        }

        return $sections;
    }

    public function section(string $section, array $context = [], int $limit = 8): EloquentCollection
    {
        $query = $this->base($context);

        match ($section) {
            'most_viewed' => $query->mostViewed(),
            'trending' => $query->trending(),
            'relevant' => $query->mostRelevant(),
            'recent' => $query->recent(),
            default => $query->mostPopular(),
        };

        return $query->limit($limit)->get();
    }

    /** Videos on a place page, best match first. */
    public function forPlace(Place $place, string $section = 'popular', int $limit = 12): EloquentCollection
    {
        $query = $place->publicVideos()
            ->with(['city', 'country', 'category'])
            ->orderByDesc('place_video.is_primary');

        match ($section) {
            'most_viewed' => $query->orderByDesc('videos.view_count'),
            'trending' => $query->orderByDesc('videos.trending_score'),
            'relevant' => $query->orderByDesc('place_video.match_score'),
            'recent' => $query->orderByDesc('videos.published_at'),
            default => $query->orderByDesc('videos.popularity_score'),
        };

        return $query->limit($limit)->get();
    }

    /**
     * The GoodTripLove TV playlist. It follows the visitor's context, then
     * widens: same city → same country → anywhere, so the player is never
     * empty on a thin catalogue.
     */
    public function tvPlaylist(array $context = [], int $limit = null): EloquentCollection
    {
        $limit ??= (int) config('goodtriplove.tv.playlist_size');

        $attempts = [
            $context,
            array_diff_key($context, ['category_id' => null]),
            array_diff_key($context, ['category_id' => null, 'city_id' => null]),
            [],
        ];

        foreach ($attempts as $attempt) {
            $videos = $this->base($attempt)
                ->where('is_tv_eligible', true)
                ->where('embeddable', true)
                ->mostPopular()
                ->limit($limit)
                ->get();

            if ($videos->count() >= min(3, $limit)) {
                return $videos;
            }
        }

        return $videos ?? new EloquentCollection;
    }

    public function featured(array $context = []): ?Video
    {
        $featured = $this->base($context)
            ->where('is_featured', true)
            ->where('embeddable', true)
            ->mostPopular()
            ->first();

        return $featured ?: $this->base($context)
            ->where('embeddable', true)
            ->mostPopular()
            ->first();
    }

    /** Videos near a given one — same city and category, itself excluded. */
    public function similarTo(Video $video, int $limit = 8): EloquentCollection
    {
        return $this->base([
            'country_id' => $video->country_id,
            'city_id' => $video->city_id,
            'category_id' => $video->category_id,
        ])
            ->whereKeyNot($video->getKey())
            ->mostPopular()
            ->limit($limit)
            ->get();
    }

    /**
     * Cached by id only. Caching hydrated Eloquent models breaks as soon as the
     * store serialises them (they come back as __PHP_Incomplete_Class), so the
     * cache holds the ordered id list and the models are re-read from the DB.
     */
    public function popularCities(int $limit = 12): EloquentCollection
    {
        $ids = Cache::remember('feed:popular-cities:'.$limit, 900, function () use ($limit) {
            return \App\Models\City::query()
                ->active()
                ->withCount(['videos' => fn ($q) => $q->public()])
                ->orderByDesc('is_popular')
                ->orderByDesc('videos_count')
                ->limit($limit)
                ->pluck('id')
                ->all();
        });

        if ($ids === []) {
            return new EloquentCollection;
        }

        $positions = array_flip($ids);

        // Ordered in PHP rather than with FIELD(), which is MySQL-only.
        return \App\Models\City::query()
            ->whereKey($ids)
            ->with('country')
            ->withCount(['videos' => fn ($q) => $q->public()])
            ->get()
            ->sortBy(fn ($city) => $positions[$city->id] ?? PHP_INT_MAX)
            ->values();
    }

    private function base(array $context = []): \Illuminate\Database\Eloquent\Builder
    {
        return Video::query()
            ->public()
            ->with(['city', 'country', 'category'])
            ->inContext(
                $context['country_id'] ?? null,
                $context['city_id'] ?? null,
                $context['category_id'] ?? null,
            )
            ->when($context['language'] ?? null, fn ($q, $language) => $q->where('language', $language));
    }
}
