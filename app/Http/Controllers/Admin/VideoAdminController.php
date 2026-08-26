<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\Video;
use App\Services\AuditService;
use App\Services\VideoScorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        $videos = Video::query()
            ->with(['city', 'country', 'category', 'places'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('country'), fn ($q, $id) => $q->where('country_id', $id))
            ->when($request->query('city'), fn ($q, $id) => $q->where('city_id', $id))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', '%'.$term.'%'))
            ->when($request->query('unlinked'), fn ($q) => $q->whereDoesntHave('places'))
            ->orderByDesc($request->query('sort') === 'views' ? 'view_count' : 'created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.videos.index', [
            'videos' => $videos,
            'countries' => Country::orderBy('sort_order')->get(),
            'categories' => Category::roots()->orderBy('sort_order')->get(),
            'statusCounts' => Video::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function edit(Video $video): View
    {
        $video->load(['places.city', 'city', 'country', 'category']);

        return view('admin.videos.edit', [
            'video' => $video,
            'countries' => Country::orderBy('sort_order')->get(),
            'cities' => City::where('country_id', $video->country_id)->orderBy('slug')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
            'places' => Place::published()
                ->when($video->city_id, fn ($q) => $q->where('city_id', $video->city_id))
                ->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'language' => ['nullable', 'string', 'max:8'],
            'is_featured' => ['boolean'],
            'is_tv_eligible' => ['boolean'],
        ]);

        $old = $video->only(array_keys($data));

        $video->fill($data + [
            'is_featured' => $request->boolean('is_featured'),
            'is_tv_eligible' => $request->boolean('is_tv_eligible'),
        ])->save();

        $this->audit->record('video.update', $video, $old, $video->only(array_keys($data)));

        return back()->with('status', __('gtl.saved'));
    }

    public function approve(Request $request, Video $video): RedirectResponse
    {
        $video->update([
            'status' => Video::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->audit->record('video.approve', $video, [], ['status' => Video::STATUS_APPROVED]);

        return back()->with('status', __('gtl.video_approved'));
    }

    public function reject(Request $request, Video $video): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $video->update([
            'status' => Video::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        $this->audit->record('video.reject', $video, [], ['status' => Video::STATUS_REJECTED]);

        return back()->with('status', __('gtl.video_rejected'));
    }

    /** Bulk approve/reject from the moderation list. */
    public function bulk(Request $request, VideoScorer $scorer): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,feature,unfeature,rescore'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $videos = Video::whereKey($data['ids'])->get();

        foreach ($videos as $video) {
            match ($data['action']) {
                'approve' => $video->update([
                    'status' => Video::STATUS_APPROVED,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]),
                'reject' => $video->update([
                    'status' => Video::STATUS_REJECTED,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]),
                'feature' => $video->update(['is_featured' => true]),
                'unfeature' => $video->update(['is_featured' => false]),
                'rescore' => $scorer->score($video)->save(),
            };
        }

        $this->audit->record('video.bulk_'.$data['action'], null, [], ['count' => $videos->count()]);

        return back()->with('status', __('gtl.bulk_done', ['count' => $videos->count()]));
    }

    public function attachPlace(Request $request, Video $video): RedirectResponse
    {
        $request->validate(['place_id' => ['required', 'exists:places,id']]);

        $video->places()->syncWithoutDetaching([
            $request->integer('place_id') => [
                'match_score' => 1.0,
                'match_reason' => 'manual',
                'confirmed' => true,
                'is_primary' => ! $video->places()->exists(),
            ],
        ]);

        return back()->with('status', __('gtl.saved'));
    }

    public function detachPlace(Video $video, Place $place): RedirectResponse
    {
        $video->places()->detach($place->id);

        return back()->with('status', __('gtl.saved'));
    }
}
