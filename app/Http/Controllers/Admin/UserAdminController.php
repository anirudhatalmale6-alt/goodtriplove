<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
                ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
                ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', '%'.$t.'%')
                    ->orWhere('email', 'like', '%'.$t.'%')))
                ->withCount('places')
                ->latest()->paginate(40)->withQueryString(),
            'roleCounts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
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
