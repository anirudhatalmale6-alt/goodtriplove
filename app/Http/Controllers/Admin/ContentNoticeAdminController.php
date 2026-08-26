<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentNotice;
use App\Services\AuditService;
use App\Services\ContentNoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The reporting queue.
 *
 * Every state change is written to the audit log as well as to the notice, so
 * the decision history stays reconstructable even if a notice is later edited.
 */
class ContentNoticeAdminController extends Controller
{
    public const DECISIONS = [
        'no_action', 'hidden', 'deindexed', 'removed',
        'visibility_limited', 'account_suspended', 'restored',
    ];

    public function __construct(
        private ContentNoticeService $notices,
        private AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.notices.index', [
            'notices' => ContentNotice::query()
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(30)->withQueryString(),
            'statusCounts' => ContentNotice::selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status'),
            'decisions' => self::DECISIONS,
        ]);
    }

    public function show(ContentNotice $notice): View
    {
        return view('admin.notices.show', [
            'notice' => $notice,
            'decisions' => self::DECISIONS,
        ]);
    }

    public function triage(Request $request, ContentNotice $notice): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:triage,under_review']]);

        $old = $notice->only('status');
        $notice->update(['status' => $data['status']]);

        $this->audit->record('notice.status', $notice, $old, $notice->only('status'));

        return back()->with('status', 'Signalement mis à jour.');
    }

    public function decide(Request $request, ContentNotice $notice): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:'.implode(',', self::DECISIONS)],
            'decision_reason' => ['required', 'string', 'max:2000'],
        ]);

        $old = $notice->only(['status', 'decision', 'decision_reason']);

        $this->notices->decide(
            $notice,
            $data['decision'],
            $data['decision_reason'],
            $request->user()->id
        );

        $this->audit->record('notice.decision', $notice->refresh(), $old, [
            'decision' => $data['decision'],
        ]);

        return back()->with('status', 'Décision enregistrée.');
    }

    /** Records that the reporter was informed of the outcome. */
    public function markNotified(ContentNotice $notice): RedirectResponse
    {
        $notice->update(['notified_at' => now()]);

        $this->audit->record('notice.notified', $notice);

        return back()->with('status', 'Notification enregistrée.');
    }
}
