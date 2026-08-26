<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Country;
use App\Services\SuspiciousUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Simple Ads Manager: advertising spaces between categories, temporary
 * promotional banners and the scrolling announcement text.
 */
class AdsController extends Controller
{
    public const PLACEMENTS = [
        'home_between_categories',
        'home_hero_below',
        'home_sidebar',
        'place_page',
        'video_page',
        'search_results',
    ];

    public function index(): View
    {
        return view('admin.ads.index', [
            'ads' => Ad::with(['country', 'city', 'category'])->orderBy('placement')->orderBy('sort_order')->get(),
            'announcements' => Announcement::orderBy('sort_order')->get(),
            'placements' => self::PLACEMENTS,
            'countries' => Country::orderBy('sort_order')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, SuspiciousUrlService $urls): RedirectResponse
    {
        $data = $this->validated($request);

        if ($urls->isSuspicious($data['target_url'] ?? null)) {
            throw ValidationException::withMessages(['target_url' => __('gtl.url_rejected')]);
        }

        Ad::create($data);

        return back()->with('status', __('gtl.saved'));
    }

    public function update(Request $request, Ad $ad, SuspiciousUrlService $urls): RedirectResponse
    {
        $data = $this->validated($request);

        if ($urls->isSuspicious($data['target_url'] ?? null)) {
            throw ValidationException::withMessages(['target_url' => __('gtl.url_rejected')]);
        }

        $ad->update($data);

        return back()->with('status', __('gtl.saved'));
    }

    public function destroy(Ad $ad): RedirectResponse
    {
        $ad->delete();

        return back()->with('status', __('gtl.deleted'));
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        Announcement::create($request->validate([
            'text' => ['required', 'array'],
            'text.*' => ['nullable', 'string', 'max:250'],
            'url' => ['nullable', 'url', 'max:255'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer'],
        ]) + ['is_active' => true]);

        return back()->with('status', __('gtl.saved'));
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validate([
            'text' => ['required', 'array'],
            'text.*' => ['nullable', 'string', 'max:250'],
            'url' => ['nullable', 'url', 'max:255'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return back()->with('status', __('gtl.saved'));
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('status', __('gtl.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:banner,promo,sponsor'],
            'placement' => ['required', 'in:'.implode(',', self::PLACEMENTS)],
            'title' => ['nullable', 'array'],
            'title.*' => ['nullable', 'string', 'max:150'],
            'subtitle' => ['nullable', 'array'],
            'subtitle.*' => ['nullable', 'string', 'max:250'],
            'cta_label' => ['nullable', 'array'],
            'cta_label.*' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'string', 'max:255'],
            'target_url' => ['nullable', 'url', 'max:255'],
            'background_color' => ['nullable', 'string', 'max:32'],
            'text_color' => ['nullable', 'string', 'max:32'],
            'locales' => ['nullable', 'array'],
            'locales.*' => ['string', 'max:5'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
