<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\SeoAiPageTranslation;
use App\Models\Video;
use Illuminate\View\View;

class SeoAiPageController extends Controller
{
    public function show(string $locale, string $slug): View
    {
        $translation = SeoAiPageTranslation::query()
            ->where('locale', $locale)->where('slug', $slug)
            ->whereHas('page', fn ($q) => $q->published())
            ->with(['page.translations', 'page.country', 'page.city', 'page.category'])
            ->firstOrFail();

        $page = $translation->page;
        $places = Place::published()
            ->when($page->country_id, fn ($q, $id) => $q->where('country_id', $id))
            ->when($page->city_id, fn ($q, $id) => $q->where('city_id', $id))
            ->when($page->category_id, fn ($q, $id) => $q->where(function ($qq) use ($id) {
                $qq->where('category_id', $id)->orWhere('subcategory_id', $id);
            }))
            ->with(['country', 'city', 'category'])->limit(12)->get();

        $videos = Video::public()->inContext($page->country_id, $page->city_id, $page->category_id)
            ->mostPopular()->limit(12)->get();

        return view('seo_ai.show', compact('page', 'translation', 'places', 'videos'));
    }
}
