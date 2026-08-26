<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\CollectorQuery;
use App\Models\CollectorRun;
use App\Models\Country;
use App\Services\VideoCollectorService;
use App\Services\YouTubeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectorController extends Controller
{
    public function index(YouTubeClient $youtube): View
    {
        return view('admin.collector.index', [
            'queries' => CollectorQuery::with(['country', 'city', 'category'])
                ->orderBy('priority')->orderBy('label')->paginate(40),
            'runs' => CollectorRun::with('collectorQuery')->latest()->limit(20)->get(),
            'countries' => Country::orderBy('sort_order')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
            'quota' => [
                'configured' => $youtube->isConfigured(),
                'used' => $youtube->isConfigured() ? $youtube->unitsUsedToday() : 0,
                'remaining' => $youtube->isConfigured() ? $youtube->unitsRemaining() : 0,
                'limit' => (int) config('core_operations.youtube.daily_quota'),
                'search_cost' => (int) config('goodtriplove.youtube.cost.search'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'query' => ['required', 'string', 'max:200'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'locale' => ['nullable', 'string', 'max:5'],
            'region_code' => ['nullable', 'string', 'size:2'],
            'max_results' => ['nullable', 'integer', 'between:5,50'],
            'priority' => ['nullable', 'integer', 'between:1,999'],
            'interval_hours' => ['nullable', 'integer', 'between:1,8760'],
        ]);

        CollectorQuery::create($data + ['is_active' => true]);

        return back()->with('status', __('gtl.saved'));
    }

    public function update(Request $request, CollectorQuery $query): RedirectResponse
    {
        $query->update($request->validate([
            'label' => ['required', 'string', 'max:150'],
            'query' => ['required', 'string', 'max:200'],
            'priority' => ['nullable', 'integer', 'between:1,999'],
            'interval_hours' => ['nullable', 'integer', 'between:1,8760'],
            'max_results' => ['nullable', 'integer', 'between:5,50'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return back()->with('status', __('gtl.saved'));
    }

    public function destroy(CollectorQuery $query): RedirectResponse
    {
        $query->delete();

        return back()->with('status', __('gtl.deleted'));
    }

    /**
     * Runs one query immediately. Kept manual and explicit because a search
     * costs 100 of the 10 000 daily units.
     */
    public function run(CollectorQuery $query, VideoCollectorService $collector): RedirectResponse
    {
        $result = $collector->runQuery($query);

        return back()->with('status', __('gtl.collector_ran', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'units' => $result['units'],
        ]));
    }

    /** Generates the standard query set for a country from its cities. */
    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'locale' => ['required', 'string', 'max:5'],
            'limit_cities' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $country = Country::findOrFail($data['country_id']);
        $categories = Category::active()->roots()->orderBy('sort_order')->get();

        $cities = City::where('country_id', $country->id)->active()
            ->orderByDesc('is_popular')
            ->limit($data['limit_cities'] ?? 5)
            ->get();

        $created = 0;

        foreach ($cities as $city) {
            foreach ($categories as $category) {
                $terms = $category->searchTerms($data['locale']);
                $term = $terms[0] ?? $category->displayName($data['locale']);

                if (! $term) {
                    continue;
                }

                $queryString = trim($term.' '.$city->displayName($data['locale']));

                $exists = CollectorQuery::where('query', $queryString)
                    ->where('city_id', $city->id)->exists();

                if ($exists) {
                    continue;
                }

                CollectorQuery::create([
                    'label' => $city->displayName($data['locale']).' · '.$category->displayName($data['locale']),
                    'query' => $queryString,
                    'country_id' => $country->id,
                    'city_id' => $city->id,
                    'category_id' => $category->id,
                    'locale' => $data['locale'],
                    'region_code' => $country->code,
                    'max_results' => 25,
                    'priority' => $city->is_popular ? 50 : 100,
                    'interval_hours' => 168,
                    'is_active' => true,
                ]);

                $created++;
            }
        }

        return back()->with('status', __('gtl.collector_generated', ['count' => $created]));
    }
}
