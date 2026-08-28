<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional accounts.
 *
 * The general member list can already filter on the business role, but it says
 * nothing about what these accounts are actually here for: the establishments
 * they have submitted and whether those are still waiting on a decision. That
 * is what this screen adds, so an account with a place stuck in the queue is
 * visible without opening every record one by one.
 */
class BusinessAdminController extends Controller
{
    public function index(Request $request): View
    {
        $businesses = User::query()
            ->where('role', User::ROLE_BUSINESS)
            ->when($request->query('deleted') === '1', fn ($q) => $q->onlyTrashed())
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$t.'%')
                ->orWhere('email', 'like', '%'.$t.'%')
                ->orWhere('company_name', 'like', '%'.$t.'%')))
            ->withCount([
                'places',
                'places as pending_places_count' => fn ($q) => $q->where('status', Place::STATUS_PENDING),
                'places as published_places_count' => fn ($q) => $q->where('status', Place::STATUS_PUBLISHED),
            ])
            // Accounts with something waiting on a decision come first: that is
            // the only reason to open this screen in a hurry.
            ->orderByDesc('pending_places_count')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.businesses.index', [
            'businesses' => $businesses,
            'totals' => [
                'accounts' => User::where('role', User::ROLE_BUSINESS)->count(),
                'unverified' => User::where('role', User::ROLE_BUSINESS)->whereNull('email_verified_at')->count(),
                'pendingPlaces' => Place::where('status', Place::STATUS_PENDING)->count(),
            ],
        ]);
    }
}
