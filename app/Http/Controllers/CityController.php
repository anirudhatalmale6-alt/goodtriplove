<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Services\AdService;
use App\Services\VideoFeedService;
use Illuminate\View\View;

class CityController extends Controller
{
    public function __construct(
        private VideoFeedService $feed,
        private AdService $ads,
    ) {}

    public function show(string $locale, Country $country, City $city): View
    {
        abort_unless($city->is_active && $country->is_active, 404);

        $context = ['country_id' => $country->id, 'city_id' => $city->id];

        return view('cities.show', [
            'country' => $country,
            'city' => $city,
            'featured' => $this->feed->featured($context),
            'playlist' => $this->feed->tvPlaylist($context),
            'sections' => $this->feed->sectionsFor($context, 8),
            'categories' => Category::active()->roots()->orderBy('sort_order')->get(),
            'places' => Place::published()->where('city_id', $city->id)
                ->with('category')
                ->orderByDesc('videos_count')
                ->limit(12)->get(),
            'ads' => $this->ads->forPlacement('home_between_categories', $context),
        ]);
    }
}
