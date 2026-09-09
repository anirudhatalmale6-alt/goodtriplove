<?php

namespace App\Services\SeoAi;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\SeoAiPage;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chooses what the next page should be about.
 *
 * **Support comes from videos, not only from places.** The module was written
 * assuming a populated `places` table; this catalogue has 3 000+ videos and
 * zero places, so every candidate query returned nothing and the module could
 * never write a single page. Videos already carry country, city and category,
 * and video discovery is what the site actually offers — so they are the
 * primary signal, and places are added to the count when they exist.
 *
 * A topic therefore qualifies on `videos + places`, and the generator is handed
 * both. The day Paulo fills the places table, nothing here needs changing: the
 * counts simply get bigger and the pages get richer.
 */
class SeoAiPlanner
{
    public function nextTopic(): ?array
    {
        $candidates = collect()
            ->concat($this->cityCategoryCandidates())
            ->concat($this->countryCategoryCandidates())
            ->concat($this->cityGuideCandidates())
            ->concat($this->categoryGuideCandidates());

        if ($candidates->isEmpty()) {
            return null;
        }

        $cutoff = now()->subWeeks(config('seo_ai.topic_cooldown_weeks', 52));
        $recent = SeoAiPage::query()->where('created_at', '>=', $cutoff)->pluck('topic_key')->all();

        return $candidates
            ->reject(fn (array $c) => in_array($c['topic_key'], $recent, true))
            ->sortByDesc(fn (array $c) => $c['support_count'] * 10 + $c['priority'])
            ->first();
    }

    /* ---------------------------------------------------------------------
     | Counting what is available to write about
     * ------------------------------------------------------------------- */

    /**
     * Merges two grouped count sets into one support figure per key.
     *
     * @param  Collection<int, object>  $videoRows
     * @param  Collection<int, object>  $placeRows
     * @return Collection<string, array{key: object, videos: int, places: int, total: int}>
     */
    private function merge(Collection $videoRows, Collection $placeRows, callable $keyOf): Collection
    {
        $merged = [];

        foreach ($videoRows as $row) {
            $merged[$keyOf($row)] = ['key' => $row, 'videos' => (int) $row->support_count, 'places' => 0];
        }

        foreach ($placeRows as $row) {
            $k = $keyOf($row);

            if (isset($merged[$k])) {
                $merged[$k]['places'] = (int) $row->support_count;
            } else {
                $merged[$k] = ['key' => $row, 'videos' => 0, 'places' => (int) $row->support_count];
            }
        }

        return collect($merged)->map(fn ($e) => $e + ['total' => $e['videos'] + $e['places']]);
    }

    /** Approved, available videos are the ones a visitor can actually watch. */
    private function videoQuery()
    {
        return Video::query()
            ->where('status', Video::STATUS_APPROVED)
            ->where('is_available', true);
    }

    private function cityCategoryCandidates(): Collection
    {
        $min = config('seo_ai.min_support_city_category', 2);

        $videos = $this->videoQuery()
            ->select('city_id', 'country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->whereNotNull('category_id')
            ->groupBy('city_id', 'country_id', 'category_id')->get();

        $places = Place::published()
            ->select('city_id', 'country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->whereNotNull('category_id')
            ->groupBy('city_id', 'country_id', 'category_id')->get();

        return $this->merge($videos, $places, fn ($r) => $r->city_id.':'.$r->category_id)
            ->filter(fn ($e) => $e['total'] >= $min)
            ->map(fn ($e) => [
                'topic_key' => "city_category:{$e['key']->city_id}:{$e['key']->category_id}",
                'topic_type' => 'city_category',
                'country_id' => (int) $e['key']->country_id,
                'city_id' => (int) $e['key']->city_id,
                'category_id' => (int) $e['key']->category_id,
                'support_count' => $e['total'],
                'video_count' => $e['videos'],
                'place_count' => $e['places'],
                'priority' => 40,
            ])->values();
    }

    private function countryCategoryCandidates(): Collection
    {
        $min = config('seo_ai.min_support_country_category', 3);

        $videos = $this->videoQuery()
            ->select('country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('country_id')->whereNotNull('category_id')
            ->groupBy('country_id', 'category_id')->get();

        $places = Place::published()
            ->select('country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('country_id')->whereNotNull('category_id')
            ->groupBy('country_id', 'category_id')->get();

        return $this->merge($videos, $places, fn ($r) => $r->country_id.':'.$r->category_id)
            ->filter(fn ($e) => $e['total'] >= $min)
            ->map(fn ($e) => [
                'topic_key' => "country_category:{$e['key']->country_id}:{$e['key']->category_id}",
                'topic_type' => 'country_category',
                'country_id' => (int) $e['key']->country_id,
                'city_id' => null,
                'category_id' => (int) $e['key']->category_id,
                'support_count' => $e['total'],
                'video_count' => $e['videos'],
                'place_count' => $e['places'],
                'priority' => 30,
            ])->values();
    }

    private function cityGuideCandidates(): Collection
    {
        $min = config('seo_ai.min_support_city_guide', 3);

        $videos = $this->videoQuery()
            ->select('city_id', 'country_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->groupBy('city_id', 'country_id')->get();

        $places = Place::published()
            ->select('city_id', 'country_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->groupBy('city_id', 'country_id')->get();

        return $this->merge($videos, $places, fn ($r) => (string) $r->city_id)
            ->filter(fn ($e) => $e['total'] >= $min)
            ->map(fn ($e) => [
                'topic_key' => "city_guide:{$e['key']->city_id}",
                'topic_type' => 'city_guide',
                'country_id' => (int) $e['key']->country_id,
                'city_id' => (int) $e['key']->city_id,
                'category_id' => null,
                'support_count' => $e['total'],
                'video_count' => $e['videos'],
                'place_count' => $e['places'],
                'priority' => 20,
            ])->values();
    }

    private function categoryGuideCandidates(): Collection
    {
        $min = config('seo_ai.min_support_category_guide', 4);

        $videos = $this->videoQuery()
            ->select('category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('category_id')->groupBy('category_id')->get();

        $places = Place::published()
            ->select('category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('category_id')->groupBy('category_id')->get();

        return $this->merge($videos, $places, fn ($r) => (string) $r->category_id)
            ->filter(fn ($e) => $e['total'] >= $min)
            ->map(fn ($e) => [
                'topic_key' => "category_guide:{$e['key']->category_id}",
                'topic_type' => 'category_guide',
                'country_id' => null,
                'city_id' => null,
                'category_id' => (int) $e['key']->category_id,
                'support_count' => $e['total'],
                'video_count' => $e['videos'],
                'place_count' => $e['places'],
                'priority' => 10,
            ])->values();
    }

    /* ---------------------------------------------------------------------
     | The material handed to the model
     * ------------------------------------------------------------------- */

    public function context(array $topic): array
    {
        $country = ! empty($topic['country_id']) ? Country::find($topic['country_id']) : null;
        $city = ! empty($topic['city_id']) ? City::find($topic['city_id']) : null;
        $category = ! empty($topic['category_id']) ? Category::find($topic['category_id']) : null;

        $places = Place::published()
            ->when($topic['country_id'] ?? null, fn ($q, $id) => $q->where('country_id', $id))
            ->when($topic['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($topic['category_id'] ?? null, fn ($q, $id) => $q->where(function ($qq) use ($id) {
                $qq->where('category_id', $id)->orWhere('subcategory_id', $id);
            }))
            ->with(['country', 'city', 'category'])
            ->limit(config('seo_ai.max_supporting_places', 12))->get();

        $videos = $this->videoQuery()
            ->when($topic['country_id'] ?? null, fn ($q, $id) => $q->where('country_id', $id))
            ->when($topic['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($topic['category_id'] ?? null, fn ($q, $id) => $q->where(function ($qq) use ($id) {
                $qq->where('category_id', $id)->orWhere('subcategory_id', $id);
            }))
            ->with(['country', 'city', 'category'])
            ->orderByDesc('popularity_score')
            ->limit(config('seo_ai.max_supporting_videos', 12))->get();

        return [
            'topic' => $topic,
            // displayName(), not ->name: these columns are cast to array and
            // hold every translation, so ->name would drop a JSON blob of six
            // languages into the prompt instead of "Portugal".
            'country' => $country ? ['id' => $country->id, 'slug' => $country->slug, 'name' => $country->displayName()] : null,
            'city' => $city ? ['id' => $city->id, 'slug' => $city->slug, 'name' => $city->displayName()] : null,
            'category' => $category ? ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->displayName()] : null,
            'places' => $places->map(fn ($p) => [
                'id' => $p->id, 'slug' => $p->slug, 'name' => $p->name,
                'description' => $p->description,
                'city' => $p->city?->displayName(),
                'country' => $p->country?->displayName(),
                'category' => $p->category?->displayName(),
            ])->values()->all(),
            // The real material on this site. Titles are the creators' own, so
            // they are facts the model may cite rather than invent.
            'videos' => $videos->map(fn ($v) => [
                'id' => $v->id,
                'title' => $v->title,
                'channel' => $v->channel_title,
                'city' => $v->city?->displayName(),
                'country' => $v->country?->displayName(),
                'category' => $v->category?->displayName(),
            ])->values()->all(),
        ];
    }
}
