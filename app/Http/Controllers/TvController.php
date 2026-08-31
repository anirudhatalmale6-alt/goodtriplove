<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Services\VideoFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GoodTripLove TV — a continuous playlist that follows the visitor's
 * country / city / category, e.g. Portugal -> Porto -> Restaurants.
 */
class TvController extends Controller
{
    public function __construct(private VideoFeedService $feed) {}

    public function index(Request $request): View
    {
        [$context, $filters] = $this->resolveContext($request);

        $playlist = $this->feed->tvPlaylist($context, 24);

        return view('tv.index', [
            'playlist' => $playlist,
            'current' => $playlist->first(),
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'categories' => Category::active()->roots()->orderBy('sort_order')->get(),
            'cities' => $filters['country']
                ? $filters['country']->cities()->active()->orderByDesc('is_popular')->get()
                : collect(),
            'filters' => $filters,
        ]);
    }

    /** Used by the player to pull the next batch without a page reload. */
    public function playlist(Request $request): JsonResponse
    {
        [$context] = $this->resolveContext($request);

        $videos = $this->feed->tvPlaylist($context, (int) $request->integer('limit', 12) ?: 12);

        return response()->json([
            'items' => $videos->map(fn ($video) => [
                'id' => $video->id,
                'title' => $video->title,
                'provider' => $video->provider,
                'provider_id' => $video->provider_video_id,
                'embed_url' => $video->embedUrl(),
                'aspect' => $video->aspectRatio(),
                'thumbnail' => $video->thumbnail(),
                'duration' => $video->durationForHumans(),
                'city' => $video->city?->displayName(),
                'country' => $video->country?->displayName(),
                'url' => route('video.show', ['video' => $video]),
                'play_url' => route('video.play', ['video' => $video]),
            ])->all(),
        ]);
    }

    private function resolveContext(Request $request): array
    {
        $country = $request->filled('country')
            ? Country::active()->where('slug', $request->query('country'))->first()
            : null;

        $city = $request->filled('city')
            ? City::active()->where('slug', $request->query('city'))->first()
            : null;

        $category = $request->filled('category')
            ? Category::active()->where('slug', $request->query('category'))->first()
            : null;

        return [
            array_filter([
                'country_id' => $country?->id,
                'city_id' => $city?->id,
                'category_id' => $category?->id,
            ]),
            ['country' => $country, 'city' => $city, 'category' => $category],
        ];
    }
}
