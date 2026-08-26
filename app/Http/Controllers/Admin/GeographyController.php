<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Countries, cities and categories. Editing any of them clears the
 * classifier's lookup index, otherwise a newly added city would stay invisible
 * to the collector for an hour.
 */
class GeographyController extends Controller
{
    private const LOCALES = ['fr', 'pt', 'es', 'it', 'de', 'en'];

    public function countries(): View
    {
        return view('admin.geography.countries', [
            'countries' => Country::withCount(['cities', 'videos'])->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCountry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:2', 'unique:countries,code'],
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:120'],
            'flag_emoji' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        Country::create($data + [
            'code' => Str::upper($data['code']),
            'slug' => Str::slug($data['name'][config('goodtriplove.default_locale')] ?? $data['code']),
            'is_active' => true,
        ]);

        $this->flushIndex();

        return back()->with('status', __('gtl.saved'));
    }

    public function updateCountry(Request $request, Country $country): RedirectResponse
    {
        $country->update($request->validate([
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:3000'],
            'flag_emoji' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]));

        $this->flushIndex();

        return back()->with('status', __('gtl.saved'));
    }

    public function cities(Request $request): View
    {
        return view('admin.geography.cities', [
            'countries' => Country::orderBy('sort_order')->get(),
            'cities' => City::with('country')
                ->when($request->query('country'), fn ($q, $id) => $q->where('country_id', $id))
                ->withCount(['videos', 'places'])
                ->orderBy('country_id')->orderBy('slug')
                ->paginate(50)->withQueryString(),
        ]);
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_popular' => ['nullable', 'boolean'],
        ]);

        $base = $data['name'][config('goodtriplove.default_locale')]
            ?? collect($data['name'])->filter()->first();

        City::create($data + ['slug' => Str::slug((string) $base), 'is_active' => true]);

        $this->flushIndex();

        return back()->with('status', __('gtl.saved'));
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $city->update($request->validate([
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
        ]));

        $this->flushIndex();

        return back()->with('status', __('gtl.saved'));
    }

    public function categories(): View
    {
        return view('admin.geography.categories', [
            'categories' => Category::with('children')->roots()->orderBy('sort_order')->get(),
            'locales' => self::LOCALES,
        ]);
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:120'],
            'search_terms' => ['nullable', 'array'],
            'search_terms.*' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:64'],
            'accent_color' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
        ]);

        // Search terms arrive as one comma-separated line per language.
        if (isset($data['search_terms'])) {
            $data['search_terms'] = collect($data['search_terms'])
                ->map(fn ($line) => collect(explode(',', (string) $line))
                    ->map(fn ($t) => trim($t))->filter()->values()->all())
                ->filter(fn ($terms) => $terms !== [])
                ->all();
        }

        $category->update($data);

        $this->flushIndex();

        return back()->with('status', __('gtl.saved'));
    }

    /** Fills the city dropdown when a country is picked. */
    public function citiesFor(Country $country): JsonResponse
    {
        return response()->json([
            'cities' => $country->cities()->orderBy('slug')->get()
                ->map(fn (City $c) => ['id' => $c->id, 'name' => $c->displayName()])->all(),
        ]);
    }

    private function flushIndex(): void
    {
        Cache::forget('classifier:cities');
        Cache::forget('classifier:countries');
        Cache::forget('classifier:categories');
    }
}
