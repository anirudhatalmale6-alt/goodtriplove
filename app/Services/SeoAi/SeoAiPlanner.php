<?php

namespace App\Services\SeoAi;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\SeoAiPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    private function cityCategoryCandidates(): Collection
    {
        $min = config('seo_ai.min_places_city_category', 2);
        $rows = Place::published()->select('city_id', 'country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->whereNotNull('category_id')
            ->groupBy('city_id', 'country_id', 'category_id')->havingRaw('COUNT(*) >= ?', [$min])->get();

        return $rows->map(fn ($r) => [
            'topic_key' => "city_category:{$r->city_id}:{$r->category_id}",
            'topic_type' => 'city_category', 'country_id' => (int) $r->country_id,
            'city_id' => (int) $r->city_id, 'category_id' => (int) $r->category_id,
            'support_count' => (int) $r->support_count, 'priority' => 40,
        ]);
    }

    private function countryCategoryCandidates(): Collection
    {
        $min = config('seo_ai.min_places_country_category', 3);
        $rows = Place::published()->select('country_id', 'category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('country_id')->whereNotNull('category_id')
            ->groupBy('country_id', 'category_id')->havingRaw('COUNT(*) >= ?', [$min])->get();

        return $rows->map(fn ($r) => [
            'topic_key' => "country_category:{$r->country_id}:{$r->category_id}",
            'topic_type' => 'country_category', 'country_id' => (int) $r->country_id,
            'city_id' => null, 'category_id' => (int) $r->category_id,
            'support_count' => (int) $r->support_count, 'priority' => 30,
        ]);
    }

    private function cityGuideCandidates(): Collection
    {
        $min = config('seo_ai.min_places_city_guide', 3);
        $rows = Place::published()->select('city_id', 'country_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('city_id')->groupBy('city_id', 'country_id')->havingRaw('COUNT(*) >= ?', [$min])->get();

        return $rows->map(fn ($r) => [
            'topic_key' => "city_guide:{$r->city_id}", 'topic_type' => 'city_guide',
            'country_id' => (int) $r->country_id, 'city_id' => (int) $r->city_id,
            'category_id' => null, 'support_count' => (int) $r->support_count, 'priority' => 20,
        ]);
    }

    private function categoryGuideCandidates(): Collection
    {
        $min = config('seo_ai.min_places_category_guide', 4);
        $rows = Place::published()->select('category_id', DB::raw('COUNT(*) support_count'))
            ->whereNotNull('category_id')->groupBy('category_id')->havingRaw('COUNT(*) >= ?', [$min])->get();

        return $rows->map(fn ($r) => [
            'topic_key' => "category_guide:{$r->category_id}", 'topic_type' => 'category_guide',
            'country_id' => null, 'city_id' => null, 'category_id' => (int) $r->category_id,
            'support_count' => (int) $r->support_count, 'priority' => 10,
        ]);
    }

    public function context(array $topic): array
    {
        $country = !empty($topic['country_id']) ? Country::find($topic['country_id']) : null;
        $city = !empty($topic['city_id']) ? City::find($topic['city_id']) : null;
        $category = !empty($topic['category_id']) ? Category::find($topic['category_id']) : null;

        $places = Place::published()
            ->when($topic['country_id'] ?? null, fn ($q, $id) => $q->where('country_id', $id))
            ->when($topic['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($topic['category_id'] ?? null, fn ($q, $id) => $q->where(function ($qq) use ($id) {
                $qq->where('category_id', $id)->orWhere('subcategory_id', $id);
            }))
            ->with(['country', 'city', 'category'])
            ->limit(config('seo_ai.max_supporting_places', 12))->get();

        return [
            'topic' => $topic,
            'country' => $country ? ['id' => $country->id, 'slug' => $country->slug, 'name' => $country->name] : null,
            'city' => $city ? ['id' => $city->id, 'slug' => $city->slug, 'name' => $city->name] : null,
            'category' => $category ? ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name] : null,
            'places' => $places->map(fn ($p) => [
                'id' => $p->id, 'slug' => $p->slug, 'name' => $p->name,
                'description' => $p->description, 'city' => $p->city?->name,
                'country' => $p->country?->name, 'category' => $p->category?->name,
            ])->values()->all(),
        ];
    }
}
