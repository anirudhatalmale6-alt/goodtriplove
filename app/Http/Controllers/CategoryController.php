<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Services\AdService;
use App\Services\VideoFeedService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private VideoFeedService $feed,
        private AdService $ads,
    ) {}

    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::active()->roots()->with('children')
                ->withCount(['videos' => fn ($q) => $q->public()])
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Request $request, string $locale, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $country = $request->filled('country')
            ? Country::active()->where('slug', $request->query('country'))->first()
            : null;

        $context = array_filter([
            'category_id' => $category->id,
            'country_id' => $country?->id,
        ]);

        return view('categories.show', [
            'category' => $category,
            'country' => $country,
            'countries' => Country::active()->orderBy('sort_order')->get(),
            'featured' => $this->feed->featured($context),
            'playlist' => $this->feed->tvPlaylist($context),
            'sections' => $this->feed->sectionsFor($context, 8),
            'ads' => $this->ads->forPlacement('home_between_categories', $context),
        ]);
    }
}
