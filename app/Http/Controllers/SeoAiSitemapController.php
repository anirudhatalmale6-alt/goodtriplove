<?php

namespace App\Http\Controllers;

use App\Models\SeoAiPageTranslation;
use Illuminate\Http\Response;

class SeoAiSitemapController extends Controller
{
    public function __invoke(): Response
    {
        $items = SeoAiPageTranslation::query()
            ->whereHas('page', fn ($q) => $q->published())
            ->with('page:id,updated_at')
            ->orderBy('id')->limit(45000)->get();

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($items as $item) {
            $url = route('seo-ai.show', ['locale' => $item->locale, 'slug' => $item->slug]);
            $xml[] = '<url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc><lastmod>'.$item->page->updated_at->toAtomString().'</lastmod></url>';
        }
        $xml[] = '</urlset>';

        return response(implode('', $xml), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
