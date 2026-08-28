<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reads the administrative audit trail.
 *
 * Every admin write was already recorded; there was simply no way to look at it
 * without database access. Read-only by design — an audit log an administrator
 * can edit or delete is not an audit log.
 */
class AuditAdminController extends Controller
{
    public function index(Request $request): View
    {
        $entries = AuditEntry::query()
            ->with('actor')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))
            ->when($request->filled('actor'), fn ($q) => $q->where('actor_user_id', $request->integer('actor')))
            ->when($request->filled('success'), fn ($q) => $q->where('success', $request->string('success') === 'ok'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', [
            'entries' => $entries,
            // Only the accounts that have actually done something, so the filter
            // is not a list of every user on the site.
            'actors' => User::whereIn('id', AuditEntry::whereNotNull('actor_user_id')->distinct()->pluck('actor_user_id'))->get(),
            'actions' => AuditEntry::distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
