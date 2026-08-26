<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\AdService;
use App\Services\VideoFeedService;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function __construct(
        private VideoFeedService $feed,
        private AdService $ads,
    ) {}

    public function index(): View
    {
        return view('countries.index', [
            'countries' => Country::active()
                ->withCount([
                    'videos' => fn ($q) => $q->public(),
                    'cities' => fn ($q) => $q->where('is_active', true),
                ])
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function show(string $locale, Country $country): View
    {
        abort_unless($country->is_active, 404);

        $context = ['country_id' => $country->id];

        return view('countries.show', [
            'country' => $country,
            'featured' => $this->feed->featured($context),
            'playlist' => $this->feed->tvPlaylist($context),
            'sections' => $this->feed->sectionsFor($context, 8),
            'cities' => $country->cities()->active()
                ->withCount(['videos' => fn ($q) => $q->public()])
                ->orderByDesc('is_popular')
                ->orderByDesc('videos_count')
                ->limit(24)->get(),
            'ads' => $this->ads->forPlacement('home_between_categories', $context),
        ]);
    }
}
