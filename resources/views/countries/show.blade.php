@extends('layouts.app')
@section('title', $country->displayName().' — GoodTripLove')
@section('description', Str::limit($country->describe() ?: __('gtl.meta_country_fallback', ['country' => $country->displayName()]), 155))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ $country->flag_emoji }} {{ $country->displayName() }} <span class="dot">.</span></h1>
    <a class="section__link" href="{{ route('tv', ['country' => $country->slug]) }}">GoodTripLove TV →</a>
</div>

@if ($featured)
<div class="stage">
    <div><x-player :video="$featured" :stage="true"/></div>
    <x-tv-panel :playlist="$playlist"/>
</div>
@endif

@if ($cities->isNotEmpty())
<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.section_cities') }} <span class="dot">.</span></h2></div>
    <div class="grid grid--6">
        @foreach ($cities as $city)
            <a class="cat" href="{{ route('city.show', ['country' => $country->slug, 'city' => $city->slug]) }}">
                <div class="cat__name">{{ $city->displayName() }}</div>
                <div class="cat__count">{{ $city->videos_count }} {{ __('gtl.videos') }}</div>
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
