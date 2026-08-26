<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sections as $section)
    <sitemap>
        <loc>{{ route('sitemap.section', ['section' => $section]) }}</loc>
        <lastmod>{{ $lastmod }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
