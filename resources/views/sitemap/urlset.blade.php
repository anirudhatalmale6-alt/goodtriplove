{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach ($locales as $locale)
    <url>
        <loc>{{ route($entry['route'], array_merge($entry['params'], ['locale' => $locale])) }}</loc>
        @if (!empty($entry['lastmod']))<lastmod>{{ \Illuminate\Support\Carbon::parse($entry['lastmod'])->toAtomString() }}</lastmod>@endif
@foreach ($locales as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate }}" href="{{ route($entry['route'], array_merge($entry['params'], ['locale' => $alternate])) }}"/>
@endforeach
    </url>
@endforeach
@endforeach
</urlset>
