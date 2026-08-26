@extends('layouts.app')
@section('title', $city->displayName().', '.$country->displayName().' — GoodTripLove')
@section('description', Str::limit($city->describe() ?: __('gtl.meta_city_fallback', ['city' => $city->displayName(), 'country' => $country->displayName()]), 155))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ $city->displayName() }} <span class="dot">.</span></h1>
    <a class="section__link" href="{{ route('country.show', ['country' => $country->slug]) }}">{{ $country->flag_emoji }} {{ $country->displayName() }} →</a>
</div>

@if ($featured)
<div class="stage">
    <div><x-player :video="$featured" :stage="true"/></div>
    <x-tv-panel :playlist="$playlist"/>
</div>
@endif

@if ($categories->isNotEmpty())
<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.explore_by_category') }} <span class="dot">.</span></h2></div>
    <div class="cat-rail">
        @foreach ($categories as $category)
            <a class="cat" href="{{ route('category.show', ['category' => $category->slug, 'country' => $country->slug]) }}">
                <div class="cat__icon">{{ $category->icon ?? '📍' }}</div>
                <div class="cat__name">{{ $category->displayName() }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($places->isNotEmpty())
<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.section_places') }} <span class="dot">.</span></h2></div>
    <div class="grid grid--6">
        @foreach ($places as $place)
            <a class="cat" href="{{ route('place.show', ['country' => $country->slug, 'city' => $city->slug, 'place' => $place->slug]) }}">
                <div class="cat__name">{{ $place->name }}</div>
                <div class="cat__count">{{ $place->category?->displayName() }} · {{ $place->videos_count }} {{ __('gtl.videos') }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

@php $titles = ['popular'=>'section_most_popular','most_viewed'=>'section_most_viewed','trending'=>'section_trending','relevant'=>'section_most_relevant','recent'=>'section_recent']; @endphp
@foreach ($sections as $key => $videos)
    <x-video-section :title="__('gtl.'.$titles[$key])" :videos="$videos" :columns="4"/>
    @if ($loop->first && $ads->isNotEmpty())<x-ad-slot :ad="$ads->first()"/>@endif
@endforeach
@endsection
