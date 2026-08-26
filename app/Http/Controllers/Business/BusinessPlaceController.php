<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\Video;
use App\Services\DuplicateSubmissionService;
use App\Services\SecurityLogger;
use App\Services\SuspiciousUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Free business registration.
 *
 * A business owner describes their place and it enters the moderation queue —
 * nothing a visitor submits is published without an administrator approving it.
 */
class BusinessPlaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('business.index', [
            'places' => $request->user()->places()->with(['city', 'category'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('business.form', [
            'place' => new Place,
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'categories' => Category::active()->roots()->with('children')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(
        Request $request,
        DuplicateSubmissionService $duplicates,
        SuspiciousUrlService $urls,
        SecurityLogger $logger,
    ): RedirectResponse {
        $this->assertVerified($request);

        $data = $this->validated($request);

        if ($urls->isSuspicious($data['website'] ?? null)) {
            throw ValidationException::withMessages(['website' => __('gtl.url_rejected')]);
        }

        if ($duplicates->seenRecently('place_submission', [
            'name' => $data['name'],
            'city_id' => $data['city_id'],
            'user' => $request->user()->id,
        ])) {
            throw ValidationException::withMessages(['name' => __('gtl.duplicate_submission')]);
        }

        $city = City::findOrFail($data['city_id']);

        $place = Place::create($data + [
            'country_id' => $city->country_id,
            'owner_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($data['name'], $city->id),
            'status' => Place::STATUS_PENDING,
            'source' => 'business',
        ]);

        $logger->log('place_submitted', true, 'info', ['place_id' => $place->id]);

        return redirect()->route('business.index')->with('status', __('gtl.place_submitted'));
    }

    public function edit(Request $request, string $locale, Place $place): View
    {
        $this->authorizeOwner($request, $place);

        return view('business.form', [
            'place' => $place,
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'categories' => Category::active()->roots()->with('children')->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * An edit sends a published listing back to moderation: otherwise an
     * approved page could be rewritten into anything after the fact.
     */
    public function update(Request $request, string $locale, Place $place): RedirectResponse
    {
        $this->authorizeOwner($request, $place);

        $data = $this->validated($request);
        $city = City::findOrFail($data['city_id']);

        $place->update($data + [
            'country_id' => $city->country_id,
            'status' => Place::STATUS_PENDING,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        return redirect()->route('business.index')->with('status', __('gtl.place_updated'));
    }

    /**
     * A business may propose one of its own YouTube videos. It is stored
     * pending like any collected video — proposing is not publishing.
     */
    public function attachVideo(Request $request, string $locale, Place $place): RedirectResponse
    {
        $this->authorizeOwner($request, $place);

        $request->validate(['youtube_url' => ['required', 'string', 'max:255']]);

        $videoId = $this->extractYoutubeId((string) $request->input('youtube_url'));

        if (! $videoId) {
            throw ValidationException::withMessages(['youtube_url' => __('gtl.invalid_youtube_url')]);
        }

        $video = Video::firstOrCreate(
            ['provider' => 'youtube', 'provider_video_id' => $videoId],
            [
                'title' => $place->name,
                'status' => Video::STATUS_PENDING,
                'source' => 'user',
                'submitted_by' => $request->user()->id,
                'country_id' => $place->country_id,
                'city_id' => $place->city_id,
                'category_id' => $place->category_id,
            ]
        );

        $video->places()->syncWithoutDetaching([
            $place->id => ['match_score' => 1.0, 'match_reason' => 'owner', 'confirmed' => false],
        ]);

        return back()->with('status', __('gtl.video_submitted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city_id' => ['required', 'exists:cities,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:3000'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'url', 'max:255'],
            'price_level' => ['nullable', 'integer', 'between:1,4'],
        ]);
    }

    private function assertVerified(Request $request): void
    {
        if (! $request->user()->email_verified_at) {
            abort(redirect()->route('verification.notice'));
        }
    }

    private function authorizeOwner(Request $request, Place $place): void
    {
        abort_unless(
            $place->owner_id === $request->user()->id || $request->user()->isStaff(),
            403
        );
    }

    private function uniqueSlug(string $name, int $cityId): string
    {
        $base = Str::slug($name) ?: 'place';
        $slug = $base;
        $i = 2;

        while (Place::where('city_id', $cityId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function extractYoutubeId(string $url): ?string
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return $m[1];
        }

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $url) ? $url : null;
    }
}
