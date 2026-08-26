<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\Video;
use Illuminate\Http\Response;

/**
 * A sitemap index plus one file per section. Every URL is emitted once per
 * language with reciprocal hreflang links, so the six locales are treated as
 * alternates rather than duplicates.
 */
class SitemapController extends Controller
{
    private const SECTIONS = ['pages', 'countries', 'cities', 'categories', 'places', 'videos'];

    public function index(): Response
    {
        $xml = view('sitemap.index', [
            'sections' => self::SECTIONS,
            'lastmod' => now()->toAtomString(),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function section(string $section): Response
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $entries = match ($section) {
            'countries' => Country::active()->get()
                ->map(fn ($c) => ['route' => 'country.show', 'params' => ['country' => $c->slug], 'lastmod' => $c->updated_at]),
            'cities' => City::active()->with('country')->get()
                ->map(fn ($c) => ['route' => 'city.show', 'params' => ['country' => $c->country->slug, 'city' => $c->slug], 'lastmod' => $c->updated_at]),
            'categories' => Category::active()->get()
                ->map(fn ($c) => ['route' => 'category.show', 'params' => ['category' => $c->slug], 'lastmod' => $c->updated_at]),
            'places' => Place::published()->with('city.country')->limit(40000)->get()
                ->filter(fn ($p) => $p->city && $p->city->country)
                ->map(fn ($p) => ['route' => 'place.show', 'params' => [
                    'country' => $p->city->country->slug, 'city' => $p->city->slug, 'place' => $p->slug,
                ], 'lastmod' => $p->updated_at]),
            'videos' => Video::public()->orderByDesc('popularity_score')->limit(40000)->get()
                ->map(fn ($v) => ['route' => 'video.show', 'params' => ['video' => $v->id], 'lastmod' => $v->updated_at]),
            default => collect([
                ['route' => 'home', 'params' => [], 'lastmod' => now()],
                ['route' => 'videos.index', 'params' => [], 'lastmod' => now()],
                ['route' => 'tv', 'params' => [], 'lastmod' => now()],
                ['route' => 'countries.index', 'params' => [], 'lastmod' => now()],
                ['route' => 'categories.index', 'params' => [], 'lastmod' => now()],
                ['route' => 'app.download', 'params' => [], 'lastmod' => now()],
            ]),
        };

        $xml = view('sitemap.urlset', [
            'entries' => $entries,
            'locales' => array_keys(config('goodtriplove.locales')),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /*/business',
            'Disallow: /*/favorites',
            'Disallow: /*/login',
            'Disallow: /*/register',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
