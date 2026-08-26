<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q'));

        $country = $request->filled('country')
            ? Country::active()->where('slug', $request->query('country'))->first() : null;
        $city = $request->filled('city')
            ? City::active()->where('slug', $request->query('city'))->first() : null;
        $category = $request->filled('category')
            ? Category::active()->where('slug', $request->query('category'))->first() : null;

        $videos = collect();
        $places = collect();

        if ($term !== '' || $country || $city || $category) {
            $videos = Video::query()->public()->with(['city', 'country', 'category'])
                ->inContext($country?->id, $city?->id, $category?->id)
                ->when($term !== '', fn ($q) => $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', '%'.$term.'%')
                        ->orWhere('channel_title', 'like', '%'.$term.'%');
                }))
                ->mostPopular()
                ->paginate(24)
                ->withQueryString();

            $places = Place::published()->with(['city', 'category'])
                ->when($country, fn ($q) => $q->where('country_id', $country->id))
                ->when($city, fn ($q) => $q->where('city_id', $city->id))
                ->when($category, fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('category_id', $category->id)
                    ->orWhere('subcategory_id', $category->id)))
                ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                ->orderByDesc('videos_count')
                ->limit(12)
                ->get();
        }

        return view('search', [
            'term' => $term,
            'videos' => $videos,
            'places' => $places,
            'country' => $country,
            'city' => $city,
            'category' => $category,
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'categories' => Category::active()->roots()->orderBy('sort_order')->get(),
            'cities' => $country ? $country->cities()->active()->orderByDesc('is_popular')->get() : collect(),
        ]);
    }

    /** Type-ahead for the hero search bar. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (Str::length($term) < 2) {
            return response()->json(['items' => []]);
        }

        $cities = City::active()->with('country')
            ->where('name', 'like', '%'.$term.'%')
            ->orWhere('slug', 'like', Str::slug($term).'%')
            ->limit(5)->get()
            ->map(fn (City $city) => [
                'type' => 'city',
                'label' => $city->displayName(),
                'sub' => $city->country?->displayName(),
                'url' => route('city.show', ['country' => $city->country, 'city' => $city]),
            ]);

        $places = Place::published()->with('city')
            ->where('name', 'like', '%'.$term.'%')
            ->limit(5)->get()
            ->map(fn (Place $place) => [
                'type' => 'place',
                'label' => $place->name,
                'sub' => $place->city?->displayName(),
                'url' => route('place.show', [
                    'country' => $place->country_id ? \App\Models\Country::find($place->country_id) : null,
                    'city' => $place->city,
                    'place' => $place,
                ]),
            ]);

        return response()->json(['items' => $cities->concat($places)->values()->all()]);
    }
}
