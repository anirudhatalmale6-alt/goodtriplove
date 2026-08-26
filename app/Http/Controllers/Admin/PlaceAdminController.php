<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlaceAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        return view('admin.places.index', [
            'places' => Place::with(['city', 'country', 'category', 'owner'])
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->when($request->query('q'), fn ($q, $t) => $q->where('name', 'like', '%'.$t.'%'))
                ->latest()->paginate(30)->withQueryString(),
            'statusCounts' => Place::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.places.form', [
            'place' => new Place(['status' => Place::STATUS_PUBLISHED]),
            'countries' => Country::orderBy('sort_order')->get(),
            'cities' => collect(),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $city = City::findOrFail($data['city_id']);

        $place = Place::create($data + [
            'country_id' => $city->country_id,
            'slug' => $this->uniqueSlug($data['name'], $city->id),
            'source' => 'admin',
            'published_at' => $data['status'] === Place::STATUS_PUBLISHED ? now() : null,
        ]);

        $this->audit->record('place.create', $place, [], $place->only(array_keys($data)));

        return redirect()->route('admin.places.edit', $place)->with('status', __('gtl.saved'));
    }

    public function edit(Place $place): View
    {
        return view('admin.places.form', [
            'place' => $place,
            'countries' => Country::orderBy('sort_order')->get(),
            'cities' => City::where('country_id', $place->country_id)->orderBy('slug')->get(),
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Place $place): RedirectResponse
    {
        $data = $this->validated($request);
        $city = City::findOrFail($data['city_id']);
        $old = $place->only(array_keys($data));

        $place->update($data + [
            'country_id' => $city->country_id,
            'published_at' => $data['status'] === Place::STATUS_PUBLISHED
                ? ($place->published_at ?? now())
                : null,
        ]);

        $this->audit->record('place.update', $place, $old, $place->only(array_keys($data)));

        return back()->with('status', __('gtl.saved'));
    }

    public function approve(Request $request, Place $place): RedirectResponse
    {
        $place->update([
            'status' => Place::STATUS_PUBLISHED,
            'published_at' => $place->published_at ?? now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->audit->record('place.approve', $place, [], ['status' => Place::STATUS_PUBLISHED]);

        return back()->with('status', __('gtl.place_approved'));
    }

    public function reject(Request $request, Place $place): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $place->update([
            'status' => Place::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        $this->audit->record('place.reject', $place, [], ['status' => Place::STATUS_REJECTED]);

        return back()->with('status', __('gtl.place_rejected'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city_id' => ['required', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'url', 'max:255'],
            'price_level' => ['nullable', 'integer', 'between:1,4'],
            'status' => ['required', 'in:draft,pending,published,rejected'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
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
}
