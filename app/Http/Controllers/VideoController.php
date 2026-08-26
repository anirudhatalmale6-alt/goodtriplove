<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Video;
use App\Services\VideoFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function __construct(private VideoFeedService $feed) {}

    /** Top vidéos — the ranked listing with the five sort modes. */
    public function index(Request $request): View
    {
        $section = in_array($request->query('sort'), VideoFeedService::SECTIONS, true)
            ? $request->query('sort')
            : 'popular';

        $country = $request->filled('country')
            ? Country::active()->where('slug', $request->query('country'))->first()
            : null;

        $city = $request->filled('city')
            ? City::active()->where('slug', $request->query('city'))->first()
            : null;

        $category = $request->filled('category')
            ? Category::active()->where('slug', $request->query('category'))->first()
            : null;

        $context = array_filter([
            'country_id' => $country?->id,
            'city_id' => $city?->id,
            'category_id' => $category?->id,
        ]);

        $query = Video::query()->public()->with(['city', 'country', 'category'])
            ->inContext($context['country_id'] ?? null, $context['city_id'] ?? null, $context['category_id'] ?? null);

        match ($section) {
            'most_viewed' => $query->mostViewed(),
            'trending' => $query->trending(),
            'relevant' => $query->mostRelevant(),
            'recent' => $query->recent(),
            default => $query->mostPopular(),
        };

        return view('videos.index', [
            'videos' => $query->paginate(24)->withQueryString(),
            'section' => $section,
            'country' => $country,
            'city' => $city,
            'category' => $category,
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'categories' => Category::active()->roots()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Request $request, string $locale, Video $video): View
    {
        abort_unless($video->status === Video::STATUS_APPROVED && $video->is_available, 404);

        $video->load(['city.country', 'country', 'category', 'places.city']);

        return view('videos.show', [
            'video' => $video,
            'place' => $video->primaryPlace(),
            'similar' => $this->feed->similarTo($video, 8),
            'playlist' => $this->feed->tvPlaylist(array_filter([
                'country_id' => $video->country_id,
                'city_id' => $video->city_id,
                'category_id' => $video->category_id,
            ])),
        ]);
    }

    /**
     * Counts a GoodTripLove view when the visitor actually starts the player.
     * Deduplicated per visitor per day — the counter shown on a card is not a
     * page-load counter.
     */
    public function play(Request $request, string $locale, Video $video): JsonResponse
    {
        $hash = hash('sha256', implode('|', [
            $request->session()->getId(),
            $request->ip(),
            config('app.key'),
        ]));

        $inserted = DB::table('video_views')->insertOrIgnore([
            'video_id' => $video->id,
            'visitor_hash' => $hash,
            'viewed_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted) {
            $video->increment('gtl_views');
        }

        return response()->json([
            'embed_url' => $video->embedUrl(),
            'gtl_views' => $video->gtl_views + ($inserted ? 1 : 0),
        ]);
    }
}
