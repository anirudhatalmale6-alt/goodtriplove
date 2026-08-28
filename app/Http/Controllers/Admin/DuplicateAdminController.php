<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\AuditService;
use App\Services\DuplicateFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateAdminController extends Controller
{
    public function __construct(
        private DuplicateFinder $finder,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        $groups = $this->finder->groups()->map(fn (array $group) => [
            'key' => $group['key'],
            'videos' => $group['videos'],
            'keeper' => $this->finder->suggestedKeeper($group['videos']),
        ]);

        return view('admin.videos.duplicates', [
            'groups' => $groups,
            'total' => $groups->sum(fn (array $g) => $g['videos']->count() - 1),
        ]);
    }

    /**
     * Keeps one copy and rejects the rest of the group.
     *
     * Nothing is deleted: a rejected video keeps its row, so a wrong call here
     * is visible in the video list and can be put back. The keeper must be one
     * of the ids submitted, or a hand-made request could reject an entire group
     * and leave nothing behind.
     */
    public function resolve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'keep' => ['required', 'integer', 'exists:videos,id'],
            'ids' => ['required', 'array', 'min:2'],
            'ids.*' => ['integer', 'exists:videos,id'],
        ]);

        abort_unless(in_array($data['keep'], $data['ids'], true), 422, 'Le doublon conservé doit faire partie du groupe.');

        $rejected = Video::whereIn('id', $data['ids'])
            ->where('id', '!=', $data['keep'])
            ->get();

        foreach ($rejected as $video) {
            $video->update([
                'status' => Video::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => 'Doublon de la vidéo #'.$data['keep'],
            ]);
        }

        $this->audit->record('videos.duplicates.resolve', null, [], [
            'kept' => $data['keep'],
            'rejected' => $rejected->pluck('id')->all(),
        ]);

        return back()->with('status', trans_choice(
            '{1} 1 doublon écarté.|[2,*] :count doublons écartés.',
            $rejected->count(),
            ['count' => $rejected->count()],
        ));
    }
}
