<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Services\AdService;
use App\Services\VideoFeedService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceController extends Controller
{
    public function __construct(
        private VideoFeedService $feed,
        private AdService $ads,
    ) {}

    public function show(Request $request, string $locale, Country $country, City $city, Place $place): View
    {
        abort_unless($place->isPublished(), 404);

        $place->load(['category', 'subcategory', 'city.country']);
        $place->increment('gtl_views');

        $sections = [];

        // The five per-place sections the brief asks for. Empty ones are
        // dropped so a place with three videos does not show five headings.
        foreach (VideoFeedService::SECTIONS as $section) {
            $videos = $this->feed->forPlace($place, $section, 8);

            if ($videos->isNotEmpty()) {
                $sections[$section] = $videos;
            }
        }

        return view('places.show', [
            'country' => $country,
            'city' => $city,
            'place' => $place,
            'featured' => $this->feed->forPlace($place, 'popular', 1)->first(),
            'sections' => $sections,
            'playlist' => $this->feed->tvPlaylist(array_filter([
                'country_id' => $place->country_id,
                'city_id' => $place->city_id,
                'category_id' => $place->category_id,
            ])),
            'nearby' => Place::published()
                ->where('city_id', $place->city_id)
                ->whereKeyNot($place->getKey())
                ->orderByDesc('videos_count')
                ->limit(6)->get(),
            'ads' => $this->ads->forPlacement('place_page', ['city_id' => $place->city_id]),
        ]);
    }
}
