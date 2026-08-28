@php
    $locales = config('goodtriplove.locales');
    $current = app()->getLocale();
    $assetVersion = @filemtime(public_path('css/gtl.css')) ?: 1;
    $ticker = app(\App\Services\AdService::class)->ticker();
@endphp
<!doctype html>
<html lang="{{ $current }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    // An administrator's override wins over the page's own @section; the page
    // wins over the site default. $seo comes from SeoComposer.
    $seo = $seo ?? ['title' => null, 'description' => null, 'canonical_url' => null,
                    'indexable' => true, 'structured_data' => null];
    $seoTitle = $seo['title'] ?: trim($__env->yieldContent('title', __('gtl.meta_default_title')));
    $seoDescription = $seo['description'] ?: trim($__env->yieldContent('description', __('gtl.meta_default_description')));
    $seoCanonical = $seo['canonical_url'] ?: trim($__env->yieldContent('canonical', url()->current()));
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoCanonical }}">
@unless ($seo['indexable'])
    {{-- Set from the admin: keep this page out of the search results. --}}
    <meta name="robots" content="noindex,nofollow">
@endunless
@if (!empty($seo['structured_data']))
    <script type="application/ld+json">{!! json_encode($seo['structured_data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif

{{-- Six languages, declared as alternates rather than duplicates. --}}
@hasSection('hreflang')
    @yield('hreflang')
@else
    @foreach ($locales as $code => $meta)
        <link rel="alternate" hreflang="{{ $code }}"
              href="{{ \App\Support\LocaleUrl::current($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default"
          href="{{ \App\Support\LocaleUrl::current(config('goodtriplove.default_locale')) }}">
@endif

{{-- og:title is the only title social platforms read. --}}
<meta property="og:site_name" content="GoodTripLove">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="@yield('og_title', $seoTitle)">
<meta property="og:description" content="@yield('og_description', $seoDescription)">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="{{ $current }}">
@hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif

<meta name="theme-color" content="#0a0d13">
<link rel="preconnect" href="https://i.ytimg.com" crossorigin>
<link rel="preconnect" href="https://www.youtube-nocookie.com" crossorigin>
<link rel="stylesheet" href="{{ asset('css/gtl.css') }}?v={{ $assetVersion }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
@stack('head')
</head>
<body
    data-cookie-url="{{ route('cookies.store') }}"
    data-consent-text="{{ __('gtl.consent_video_text') }}"
    data-consent-accept="{{ __('gtl.consent_video_accept') }}"
    data-consent-close="{{ __('gtl.consent_video_close') }}">

@if (! empty($ticker))
<div class="ticker" aria-label="{{ __('gtl.announcements') }}">
    <div class="ticker__track">
        {{-- Duplicated once so the loop is seamless. --}}
        @foreach (array_merge($ticker, $ticker) as $item)
            <span class="ticker__item">
                @if ($item['emoji']) <span aria-hidden="true">{{ $item['emoji'] }}</span> @endif
                @if ($item['url'])
                    <a href="{{ $item['url'] }}">{{ $item['text'] }}</a>
                @else
                    {{ $item['text'] }}
                @endif
            </span>
        @endforeach
    </div>
</div>
@endif

<header class="site-header">
    <div class="wrap site-header__inner">
        <a class="logo" href="{{ route('home') }}" aria-label="GoodTripLove">
            <svg class="logo__heart" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 21s-7.5-4.7-9.5-9A5.3 5.3 0 0 1 12 6.4 5.3 5.3 0 0 1 21.5 12c-2 4.3-9.5 9-9.5 9z"/>
            </svg>
            <span>Good<span class="logo__b">Trip</span>Love</span>
        </a>

        <nav class="nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">{{ __('gtl.nav_home') }}</a>
            <a href="{{ route('countries.index') }}" class="{{ request()->routeIs('country*') ? 'is-active' : '' }}">{{ __('gtl.nav_countries') }}</a>
            <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categor*') ? 'is-active' : '' }}">{{ __('gtl.nav_categories') }}</a>
            <a href="{{ route('videos.index') }}" class="{{ request()->routeIs('videos.*') ? 'is-active' : '' }}">{{ __('gtl.nav_top_videos') }}</a>
            <a href="{{ route('tv') }}" class="{{ request()->routeIs('tv') ? 'is-active' : '' }}">{{ __('gtl.nav_tv') }}</a>
            <a href="{{ route('app.download') }}" class="{{ request()->routeIs('app.*') ? 'is-active' : '' }}">{{ __('gtl.nav_app') }}</a>

            {{-- Shown only inside the open mobile menu (see gtl.css). --}}
            <div class="nav__mobile-only">
                @auth
                    <a class="btn btn--ghost" href="{{ route('business.index') }}">{{ __('gtl.my_space') }}</a>
                    <a class="btn btn--ghost" href="{{ route('favorites') }}">{{ __('gtl.favorites') }}</a>
                    @if (auth()->user()->isStaff())
                        <a class="btn btn--ghost" href="{{ url('/admin') }}">{{ __('gtl.admin') }}</a>
                    @endif
                @else
                    <a class="btn btn--ghost" href="{{ route('login') }}">{{ __('gtl.login') }}</a>
                    <a class="btn btn--primary" href="{{ route('register') }}">{{ __('gtl.register_free') }}</a>
                @endauth
            </div>
        </nav>

        <form class="header-search" action="{{ route('search') }}" method="get" role="search">
            <input type="search" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('gtl.search_placeholder') }}"
                   aria-label="{{ __('gtl.search_placeholder') }}">
            <button type="submit" aria-label="{{ __('gtl.search') }}">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
            </button>
        </form>

        {{-- Stands in for the search field on laptop widths, where the menu and
             the field cannot both fit. Hidden everywhere else (see gtl.css). --}}
        <a class="header-search__compact" href="{{ route('search') }}" aria-label="{{ __('gtl.search') }}">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
        </a>

        <div class="header-tools">
            <div class="lang">
                <button class="lang__btn" type="button" aria-haspopup="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>
                    </svg>
                    {{ strtoupper($current) }}
                </button>
                <div class="lang__menu">
                    @foreach ($locales as $code => $meta)
                        <a href="{{ \App\Support\LocaleUrl::current($code) }}"
                           class="{{ $code === $current ? 'is-active' : '' }}" hreflang="{{ $code }}">
                            <span aria-hidden="true">{{ $meta['flag'] }}</span> {{ $meta['native'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @auth
                <a class="icon-btn" href="{{ route('favorites') }}" aria-label="{{ __('gtl.favorites') }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20s-6.5-4-8.2-7.8A4.6 4.6 0 0 1 12 6.6a4.6 4.6 0 0 1 8.2 5.6C18.5 16 12 20 12 20z"/>
                    </svg>
                </a>
                @if (auth()->user()->isStaff())
                    <a class="btn btn--ghost btn--sm" href="{{ url('/admin') }}">{{ __('gtl.admin') }}</a>
                @endif
                <a class="btn btn--ghost btn--sm" href="{{ route('business.index') }}">{{ __('gtl.my_space') }}</a>
                <form action="{{ route('logout') }}" method="post">@csrf
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('gtl.logout') }}</button>
                </form>
            @else
                <a class="btn btn--ghost btn--sm" href="{{ route('login') }}">{{ __('gtl.login') }}</a>
                <a class="btn btn--primary btn--sm" href="{{ route('register') }}">{{ __('gtl.register_free') }}</a>
            @endauth

            <button class="icon-btn burger" type="button" aria-label="Menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
            </button>
        </div>
    </div>
</header>

<main class="page">
    <div class="wrap">
        @if (session('status'))
            <div class="alert alert--ok">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert--warn">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert--err">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <a class="logo" href="{{ route('home') }}">
                    <svg class="logo__heart" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 21s-7.5-4.7-9.5-9A5.3 5.3 0 0 1 12 6.4 5.3 5.3 0 0 1 21.5 12c-2 4.3-9.5 9-9.5 9z"/>
                    </svg>
                    <span>Good<span class="logo__b">Trip</span>Love</span>
                </a>
                {{-- Editable from the admin; falls back to the translation
                     file until an administrator saves something. --}}
                <p style="margin-top:12px;max-width:34ch">{{ ($site['footer_pitch'] ?? '') ?: __('gtl.footer_pitch') }}</p>

                @if (! empty($siteSocial ?? []))
                    <div class="footer-social">
                        @foreach ($siteSocial ?? [] as $link)
                            <a href="{{ $link['url'] }}" rel="noopener noreferrer" target="_blank">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endif

                @if (($site['contact_email'] ?? '') || ($site['contact_phone'] ?? '') || ($site['contact_address'] ?? ''))
                    <div class="footer-contact">
                        @if ($site['contact_email'] ?? '')
                            <a href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a>
                        @endif
                        @if ($site['contact_phone'] ?? '')
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a>
                        @endif
                        @if ($site['contact_address'] ?? '')
                            <span>{{ $site['contact_address'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div>
                <h4>{{ __('gtl.nav_categories') }}</h4>
                @foreach (\App\Models\Category::active()->roots()->orderBy('sort_order')->limit(6)->get() as $footerCategory)
                    <a href="{{ route('category.show', ['category' => $footerCategory->slug]) }}">{{ $footerCategory->displayName() }}</a>
                @endforeach
            </div>
            <div>
                <h4>{{ __('gtl.nav_countries') }}</h4>
                @foreach (\App\Models\Country::active()->orderBy('sort_order')->limit(6)->get() as $footerCountry)
                    <a href="{{ route('country.show', ['country' => $footerCountry->slug]) }}">{{ $footerCountry->displayName() }}</a>
                @endforeach
            </div>
            <div>
                <h4>GoodTripLove</h4>
                <a href="{{ route('tv') }}">{{ __('gtl.nav_tv') }}</a>
                <a href="{{ route('videos.index') }}">{{ __('gtl.nav_top_videos') }}</a>
                <a href="{{ route('app.download') }}">{{ __('gtl.nav_app') }}</a>
                <a href="{{ route('register') }}">{{ __('gtl.add_your_place') }}</a>
                <a href="{{ route('legal.index') }}">{{ __('gtl.legal_centre') }}</a>
                <a href="{{ route('legal.report') }}">{{ __('gtl.report_content_title') }}</a>
                <a href="#" data-cookie-settings>{{ __('gtl.manage_cookies') }}</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ $site['site_name'] ?? 'GoodTripLove' }} — {{ __('gtl.footer_rights') }}</span>
            <span>{{ __('gtl.footer_embed_notice') }}</span>
        </div>
    </div>
</footer>

<x-cookie-banner/>

<script src="{{ asset('js/gtl.js') }}?v={{ $assetVersion }}" defer></script>
@stack('scripts')
</body>
</html>
