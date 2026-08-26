@extends('layouts.app')

@section('title', __('gtl.meta_home_title'))
@section('description', __('gtl.meta_home_description'))

@section('content')

@if ($featured)
    <div class="stage">
        <div>
            <x-player :video="$featured" :stage="true"/>

            <div class="meta-card">
                <div class="meta-card__top">
                    <div>
                        <h1 data-tv-title>{{ $featured->title }}</h1>
                        <span class="meta-card__loc" data-tv-location>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                                <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>
                            </svg>
                            {{ collect([$featured->city?->displayName(), $featured->country?->displayName()])->filter()->implode(', ') }}
                        </span>
                    </div>

                    <div class="meta-card__stats">
                        <div>
                            <div class="stat__label">{{ __('gtl.views_gtl_label') }}</div>
                            <div class="stat__value" data-gtl-views="{{ $featured->provider_video_id }}">
                                {{ \App\Support\Format::number($featured->gtl_views) }}
                            </div>
                        </div>
                        <div>
                            <div class="stat__label">{{ __('gtl.views_youtube_label') }}</div>
                            <div class="stat__value">{{ \App\Support\Format::compact($featured->view_count) }}</div>
                        </div>
                    </div>
                </div>

                <div class="chips">
                    @if ($featured->category)
                        <span class="chip chip--accent">{{ $featured->category->displayName() }}</span>
                    @endif
                    @if ($featured->subcategory)
                        <span class="chip">{{ $featured->subcategory->displayName() }}</span>
                    @endif
                    @if ($featured->country)
                        <span class="chip chip--violet">#{{ $featured->country->displayName() }}</span>
                    @endif
                    @if ($featured->language)
                        <span class="chip">{{ strtoupper($featured->language) }}</span>
                    @endif
                </div>

                @if ($featured->description)
                    <p class="meta-desc">{{ Str::limit($featured->description, 210) }}</p>
                @endif
            </div>
        </div>

        <x-tv-panel :playlist="$playlist"/>
    </div>
@else
    <div class="empty">
        <h2 style="margin:0 0 8px">{{ __('gtl.empty_catalogue_title') }}</h2>
        <p style="margin:0">{{ __('gtl.empty_catalogue_body') }}</p>
    </div>
@endif

{{-- Categories --}}
@if ($categories->isNotEmpty())
<section class="section">
    <div class="section__head">
        <h2 class="section__title">{{ __('gtl.explore_by_category') }} <span class="dot">.</span></h2>
        <a class="section__link" href="{{ route('categories.index') }}">{{ __('gtl.see_all') }} →</a>
    </div>
    <div class="cat-rail">
        @foreach ($categories as $category)
            <a class="cat" href="{{ route('category.show', ['category' => $category->slug]) }}">
                <div class="cat__icon">{{ $category->icon ?? '📍' }}</div>
                <div class="cat__name">{{ $category->displayName() }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($ads->isNotEmpty())
    <x-ad-slot :ad="$ads->first()"/>
@endif

<x-video-section :title="__('gtl.section_top_videos')" :videos="$topVideos"
                 :link="route('videos.index', ['sort' => 'most_viewed'])" :columns="4"/>

<x-video-section :title="__('gtl.section_trending')" :videos="$trending"
                 :link="route('videos.index', ['sort' => 'trending'])" :columns="4"/>

{{-- One row per category, like the reference layout. Ads sit between them. --}}
@foreach ($categoryRows as $slug => $row)
    <x-video-section :title="$row['category']->displayName()" :videos="$row['videos']"
                     :link="route('category.show', ['category' => $slug])" :columns="6"/>

    @if ($loop->index === 1 && $ads->count() > 1)
        <x-ad-slot :ad="$ads->get(1)"/>
    @endif
@endforeach

<x-video-section :title="__('gtl.section_recent')" :videos="$recent"
                 :link="route('videos.index', ['sort' => 'recent'])" :columns="4"/>

{{-- Popular cities --}}
@if ($popularCities->isNotEmpty())
<section class="section">
    <div class="section__head">
        <h2 class="section__title">{{ __('gtl.section_popular_cities') }} <span class="dot">.</span></h2>
        <a class="section__link" href="{{ route('countries.index') }}">{{ __('gtl.see_all') }} →</a>
    </div>
    <div class="grid grid--5">
        @foreach ($popularCities as $city)
            <a class="cat" href="{{ route('city.show', ['country' => $city->country->slug, 'city' => $city->slug]) }}">
                <div class="cat__icon">{{ $city->country->flag_emoji ?? '🌍' }}</div>
                <div class="cat__name">{{ $city->displayName() }}</div>
                <div class="cat__count">{{ $city->videos_count }} {{ __('gtl.videos') }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- Countries --}}
@if ($countries->isNotEmpty())
<section class="section">
    <div class="section__head">
        <h2 class="section__title">{{ __('gtl.section_discover_countries') }} <span class="dot">.</span></h2>
    </div>
    <div class="grid grid--6">
        @foreach ($countries as $country)
            <a class="cat" href="{{ route('country.show', ['country' => $country->slug]) }}">
                <div class="cat__icon">{{ $country->flag_emoji ?? '🌍' }}</div>
                <div class="cat__name">{{ $country->displayName() }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

<x-app-banner :release="$androidRelease"/>

@endsection
