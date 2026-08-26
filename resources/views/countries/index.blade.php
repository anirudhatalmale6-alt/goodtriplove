@extends('layouts.app')
@section('title', __('gtl.meta_countries_title'))
@section('description', __('gtl.meta_countries_description'))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.nav_countries') }} <span class="dot">.</span></h1>
</div>
<div class="grid grid--5">
    @foreach ($countries as $country)
        <a class="cat" href="{{ route('country.show', ['country' => $country->slug]) }}">
            <div class="cat__icon" style="font-size:34px">{{ $country->flag_emoji ?? '🌍' }}</div>
            <div class="cat__name">{{ $country->displayName() }}</div>
            <div class="cat__count">{{ $country->videos_count }} {{ __('gtl.videos') }} · {{ $country->cities_count }} {{ __('gtl.cities') }}</div>
        </a>
    @endforeach
</div>
@endsection
