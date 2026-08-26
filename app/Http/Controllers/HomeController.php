<?php

namespace App\Http\Controllers;

use App\Models\AppRelease;
use App\Models\Category;
use App\Models\Country;
use App\Models\Video;
use App\Services\AdService;
use App\Services\VideoFeedService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private VideoFeedService $feed,
        private AdService $ads,
    ) {}

    public function index(Request $request): View
    {
        $context = $this->contextFrom($request);

        $categories = Category::active()->roots()->where('show_on_home', true)
            ->orderBy('sort_order')->get();

        // One row of videos per category, so the homepage reads like the
        // reference design instead of one undifferentiated grid.
        $categoryRows = $categories->take(8)->mapWithKeys(function (Category $category) use ($context) {
            $videos = $this->feed->section('popular', $context + ['category_id' => $category->id], 6);

            return $videos->isEmpty() ? [] : [$category->slug => ['category' => $category, 'videos' => $videos]];
        })->filter();

        return view('home', [
            'featured' => $this->feed->featured($context),
            'playlist' => $this->feed->tvPlaylist($context),
            'categories' => $categories,
            'categoryRows' => $categoryRows,
            'topVideos' => $this->feed->section('most_viewed', $context, 8),
            'trending' => $this->feed->section('trending', $context, 8),
            'recent' => $this->feed->section('recent', $context, 8),
            'popularCities' => $this->feed->popularCities(10),
            'countries' => Country::active()->orderBy('sort_order')->limit(12)->get(),
            'ads' => $this->ads->forPlacement('home_between_categories', $context),
            'sidebarAd' => $this->ads->first('home_sidebar', $context),
            'androidRelease' => AppRelease::where('platform', 'android')->where('is_active', true)->latest('released_at')->first(),
            'stats' => $this->stats(),
        ]);
    }

    private function stats(): array
    {
        return cache()->remember('home:stats', 600, fn () => [
            'videos' => Video::public()->count(),
            'cities' => \App\Models\City::active()->count(),
            'countries' => Country::active()->count(),
        ]);
    }

    private function contextFrom(Request $request): array
    {
        return array_filter([
            'country_id' => $request->integer('country') ?: null,
            'city_id' => $request->integer('city') ?: null,
            'category_id' => $request->integer('category') ?: null,
        ]);
    }
}
