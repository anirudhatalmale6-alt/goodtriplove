@extends('layouts.app')

@section('title', $place->name.' — '.$city->displayName().' — GoodTripLove')
@section('description', Str::limit($place->describe() ?: __('gtl.meta_place_fallback', ['name' => $place->name, 'city' => $city->displayName()]), 155))

@push('head')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $place->name,
    'address' => array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => $place->address,
        'postalCode' => $place->postal_code,
        'addressLocality' => $city->displayName(),
        'addressCountry' => $country->code,
    ]),
    'geo' => $place->latitude && $place->longitude ? [
        '@type' => 'GeoCoordinates',
        'latitude' => $place->latitude,
        'longitude' => $place->longitude,
    ] : null,
    'telephone' => $place->phone,
    'url' => url()->current(),
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<div class="stage">
    <div>
        @if ($featured)
            <x-player :video="$featured" :stage="true"/>
        @elseif ($place->cover_image)
            <div class="player"><img class="player__thumb" src="{{ $place->cover_image }}" alt="{{ $place->name }}"></div>
        @endif

        <div class="meta-card">
            <div class="meta-card__top">
                <div>
                    <h1>{{ $place->name }}</h1>
                    <span class="meta-card__loc">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                            <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>
                        </svg>
                        {{ $city->displayName() }}, {{ $country->displayName() }}
                    </span>
                </div>
                <div class="meta-card__stats">
                    <div>
                        <div class="stat__label">{{ __('gtl.videos') }}</div>
                        <div class="stat__value">{{ $place->videos_count }}</div>
                    </div>
                    <div>
                        <div class="stat__label">{{ __('gtl.views_gtl_label') }}</div>
                        <div class="stat__value">{{ \App\Support\Format::number($place->gtl_views) }}</div>
                    </div>
                </div>
                <div class="meta-card__acts">
                    <button class="btn btn--ghost btn--sm" type="button"
                            data-favorite="place" data-id="{{ $place->id }}"
                            data-guest="{{ auth()->check() ? '0' : '1' }}"
                            data-login-url="{{ route('login') }}"
                            data-url="{{ route('favorite.toggle') }}">♥ {{ __('gtl.add_to_favorites') }}</button>
                </div>
            </div>

            <div class="chips">
                @if ($place->category)<span class="chip chip--accent">{{ $place->category->displayName() }}</span>@endif
                @if ($place->subcategory)<span class="chip">{{ $place->subcategory->displayName() }}</span>@endif
                <span class="chip chip--violet">#{{ $country->displayName() }}</span>
                @if ($place->price_level)<span class="chip">{{ str_repeat('€', $place->price_level) }}</span>@endif
            </div>

            @if ($place->describe())
                <p class="meta-desc">{{ $place->describe() }}</p>
            @endif

            <div class="facts" style="margin-top:16px">
                @if ($place->address)<div class="fact"><span class="fact__k">{{ __('gtl.address') }}</span><span class="fact__v">{{ $place->address }}</span></div>@endif
                @if ($place->phone)<div class="fact"><span class="fact__k">{{ __('gtl.phone') }}</span><span class="fact__v">{{ $place->phone }}</span></div>@endif
                @if ($place->website)<div class="fact"><span class="fact__k">{{ __('gtl.website') }}</span><span class="fact__v"><a href="{{ $place->website }}" rel="nofollow noopener" target="_blank">{{ parse_url($place->website, PHP_URL_HOST) }}</a></span></div>@endif
            </div>

            @if ($place->latitude && $place->longitude)
                <div class="map-box" style="margin-top:16px">
                    <iframe loading="lazy" title="{{ $place->name }}" referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $place->longitude - 0.008 }}%2C{{ $place->latitude - 0.008 }}%2C{{ $place->longitude + 0.008 }}%2C{{ $place->latitude + 0.008 }}&amp;layer=mapnik&amp;marker={{ $place->latitude }}%2C{{ $place->longitude }}"></iframe>
                </div>
            @endif
        </div>
    </div>

    <x-tv-panel :playlist="$playlist"/>
</div>

@php
    $sectionTitles = [
        'popular' => 'section_most_popular',
        'most_viewed' => 'section_most_viewed',
        'trending' => 'section_trending',
        'relevant' => 'section_most_relevant',
        'recent' => 'section_recent',
    ];
@endphp

@foreach ($sections as $key => $videos)
    <x-video-section :title="__('gtl.'.$sectionTitles[$key])" :videos="$videos" :columns="4"/>
@endforeach

@if ($ads->isNotEmpty())
    <x-ad-slot :ad="$ads->first()"/>
@endif

@if ($nearby->isNotEmpty())
<section class="section">
    <div class="section__head">
        <h2 class="section__title">{{ __('gtl.section_nearby') }} <span class="dot">.</span></h2>
    </div>
    <div class="grid grid--6">
        @foreach ($nearby as $item)
            <a class="cat" href="{{ route('place.show', ['country' => $country->slug, 'city' => $city->slug, 'place' => $item->slug]) }}">
                <div class="cat__name">{{ $item->name }}</div>
                <div class="cat__count">{{ $item->videos_count }} {{ __('gtl.videos') }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection
