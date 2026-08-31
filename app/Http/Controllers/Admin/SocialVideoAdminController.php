<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Video;
use App\Services\AuditService;
use App\Services\Social\SocialImporter;
use App\Services\Social\SocialImportResult;
use App\Support\SocialPlatform;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One screen per platform: what has been imported, what is waiting, and the
 * form that adds a video from its URL.
 *
 * The general Videos screen still lists everything; this one exists because the
 * questions differ per platform. "Why has Instagram imported nothing" is
 * answered by the connection panel at the bottom of the Instagram page, and by
 * nothing on a combined list.
 */
class SocialVideoAdminController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private SocialImporter $importer,
    ) {}

    public function index(Request $request, string $platform): View
    {
        abort_unless(SocialPlatform::exists($platform), 404);

        $base = Video::query()->where('provider', $platform);

        $videos = (clone $base)
            ->with(['city', 'country', 'category'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('country'), fn ($q, $id) => $q->where('country_id', $id))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($sub) use ($term) {
                $sub->where('title', 'like', '%'.$term.'%')
                    ->orWhere('channel_title', 'like', '%'.$term.'%')
                    ->orWhere('original_url', 'like', '%'.$term.'%');
            }))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.social.index', [
            'platform' => $platform,
            'definition' => SocialPlatform::DEFINITIONS[$platform],
            'videos' => $videos,
            'counts' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', Video::STATUS_PENDING)->count(),
                'approved' => (clone $base)->where('status', Video::STATUS_APPROVED)->count(),
                'rejected' => (clone $base)->where('status', Video::STATUS_REJECTED)->count(),
            ],
            'countries' => Country::orderBy('sort_order')->get(),
            'categories' => Category::roots()->orderBy('sort_order')->get(),
            'cities' => City::orderBy('slug')->get(['id', 'slug', 'country_id']),
            'enabled' => (bool) SystemSettings::effective('social_'.$platform.'_enabled'),
            'requiresApproval' => (bool) SystemSettings::effective('social_require_approval'),
            'duplicateCheck' => (bool) SystemSettings::effective('social_duplicate_check'),
            'credentialPresent' => $this->credentialPresent($platform),
            'lastImport' => (clone $base)->where('source', 'admin')->max('created_at'),
        ]);
    }

    /**
     * Adds one video from its URL.
     *
     * Every refusal comes back as a message naming the reason, because the four
     * reasons need four different reactions from the administrator: paste a
     * different link, type a title, switch the platform on, or accept that the
     * video is already there.
     */
    public function store(Request $request, string $platform): RedirectResponse
    {
        abort_unless(SocialPlatform::exists($platform), 404);

        $data = $request->validate([
            'url' => ['required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:250'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $result = $this->importer->import($data['url'], $data, $request->user()?->id);

        if ($result->successful()) {
            $this->audit->record('social.import', $result->video, [], [
                'provider' => $result->video->provider,
                'url' => $result->video->original_url,
            ]);

            return redirect()
                ->route('admin.social.index', ['platform' => $result->video->provider])
                ->with('status', trim(sprintf(
                    '« %s » ajoutée. %s',
                    $result->video->title,
                    $result->message ?? '',
                )));
        }

        $error = match ($result->outcome) {
            SocialImportResult::DUPLICATE => sprintf(
                '%s Elle est enregistrée sous « %s ».',
                $result->duplicateReason,
                $result->existing->title,
            ),
            default => $result->message,
        };

        return back()->withInput()->with('error', $error);
    }

    /**
     * Takes a video off the public site without destroying the record.
     *
     * Disabling rather than deleting is what lets an administrator undo a
     * decision, and keeps the row that stops the same video being re-imported
     * tomorrow. Deleting is a separate, explicit action.
     */
    public function disable(Request $request, Video $video): RedirectResponse
    {
        $video->update([
            'is_available' => false,
            'unavailable_reason' => 'admin',
            'status' => Video::STATUS_REJECTED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $this->audit->record('social.disable', $video, [], ['provider' => $video->provider]);

        return back()->with('status', 'Vidéo retirée du site.');
    }

    public function enable(Request $request, Video $video): RedirectResponse
    {
        $video->update([
            'is_available' => true,
            'unavailable_reason' => null,
            'status' => Video::STATUS_APPROVED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $this->audit->record('social.enable', $video, [], ['provider' => $video->provider]);

        return back()->with('status', 'Vidéo publiée.');
    }

    /** True once the platform has a credential that would unlock its API. */
    private function credentialPresent(string $platform): bool
    {
        return match ($platform) {
            SocialPlatform::YOUTUBE => filled(config('goodtriplove.youtube.api_key')),
            SocialPlatform::TIKTOK => filled(config('goodtriplove.social.tiktok.access_token')),
            SocialPlatform::INSTAGRAM, SocialPlatform::FACEBOOK => filled(config('goodtriplove.social.meta.access_token')),
            default => false,
        };
    }
}
