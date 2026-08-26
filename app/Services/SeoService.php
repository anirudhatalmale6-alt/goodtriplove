<?php

namespace App\Services;

use App\Models\SeoOverride;

class SeoService
{
    public function forPage(
        string $pageType,
        string $pageKey,
        string $locale,
        array $fallback
    ): array {
        $override = SeoOverride::where([
            'page_type' => $pageType,
            'page_key' => $pageKey,
            'locale' => $locale,
        ])->first();

        if (!$override) {
            return $fallback;
        }

        return [
            'title' => $override->title ?: ($fallback['title'] ?? null),
            'description' => $override->description ?: ($fallback['description'] ?? null),
            'canonical_url' => $override->canonical_url ?: ($fallback['canonical_url'] ?? null),
            'indexable' => $override->indexable,
            'structured_data' => $override->structured_data ?: ($fallback['structured_data'] ?? null),
        ];
    }

    public function hreflangUrls(string $routeName, array $params): array
    {
        $urls = [];

        foreach (config('growth_ops.seo.locales') as $locale) {
            $urls[$locale] = route($routeName, array_merge($params, ['locale' => $locale]));
        }

        return $urls;
    }
}
