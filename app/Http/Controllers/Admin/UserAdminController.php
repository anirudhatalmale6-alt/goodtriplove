<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEntry;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                // Deleted accounts are hidden unless they are being looked for,
                // otherwise "restore" would have nothing to act on.
                ->when($request->query('deleted') === '1', fn ($q) => $q->onlyTrashed())
                ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
                ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', '%'.$t.'%')
                    ->orWhere('email', 'like', '%'.$t.'%')))
                ->withCount('places')
                ->latest()->paginate(40)->withQueryString(),
            'roleCounts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'trashedCount' => User::onlyTrashed()->count(),
        ]);
    }

    /**
     * The full record for one account: who they are, what they have submitted,
     * and everything an administrator has done to the account. Without this the
     * only view of a member was one row in a list.
     */
    public function show(int $id): View
    {
        $user = User::withTrashed()
            ->withCount(['places', 'favorites', 'devices'])
            ->findOrFail($id);

        return view('admin.users.show', [
            'user' => $user,
            'places' => $user->places()->latest()->limit(50)->get(),
            // Both sides of the story: what was done *to* this account, and
            // what this account did as an administrator.
            'history' => AuditEntry::with('actor')
                ->where(fn ($q) => $q
                    ->where(fn ($sub) => $sub
                        ->where('auditable_type', User::class)
                        ->where('auditable_id', $user->id))
                    ->orWhere('actor_user_id', $user->id))
                ->latest('id')->limit(50)->get(),
            'securityLog' => SecurityLog::where('user_id', $user->id)
                ->latest('id')->limit(30)->get(),
        ]);
    }

    /**
     * Suspend rather than erase. The account keeps its places and can be put
     * back with restore(); Laravel's user provider refuses a sign-in for a
     * trashed account, so this really does close the door.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless($request->user()->role === User::ROLE_SUPER_ADMIN, 403);
        abort_if($user->id === $request->user()->id, 403, __('gtl.cannot_edit_self'));

        $user->delete();

        $this->audit->record('user.delete', $user, ['deleted_at' => null], ['deleted_at' => now()->toDateTimeString()]);

        return redirect()->route('admin.users.index')->with('status', __('gtl.saved'));
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);

        abort_unless($request->user()->role === User::ROLE_SUPER_ADMIN, 403);

        $user->restore();

        $this->audit->record('user.restore', $user, ['deleted_at' => 'set'], ['deleted_at' => null]);

        return back()->with('status', __('gtl.saved'));
    }

    /**
     * Only a super admin may change roles, and nobody may change their own —
     * that is how an account keeps a route back in after a mistake.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,business,moderator,admin,super_admin'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        abort_unless($request->user()->role === User::ROLE_SUPER_ADMIN, 403);
        abort_if($user->id === $request->user()->id, 403, __('gtl.cannot_edit_self'));

        $old = $user->only(['role', 'is_active']);

        $user->update([
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->audit->record('user.update', $user, $old, $user->only(['role', 'is_active']));

        return back()->with('status', __('gtl.saved'));
    }
}
