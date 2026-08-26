@extends('layouts.app')

@section('title', 'GoodTripLove TV')
@section('description', __('gtl.meta_tv_description'))

@section('content')
<div class="section__head" style="margin-bottom:16px">
    <h1 class="section__title" style="font-size:26px">GoodTripLove TV <span class="dot">.</span></h1>
    <span class="tv__live"><i></i>{{ __('gtl.live') }}</span>
</div>

<form class="filters" method="get" style="margin-bottom:20px">
    <div class="field">
        <label for="country">{{ __('gtl.country') }}</label>
        <select id="country" name="country" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($countries as $item)
                <option value="{{ $item->slug }}" @selected($filters['country']?->id === $item->id)>{{ $item->flag_emoji }} {{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    @if ($cities->isNotEmpty())
    <div class="field">
        <label for="city">{{ __('gtl.city') }}</label>
        <select id="city" name="city" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($cities as $item)
                <option value="{{ $item->slug }}" @selected($filters['city']?->id === $item->id)>{{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="field">
        <label for="category">{{ __('gtl.category') }}</label>
        <select id="category" name="category" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($categories as $item)
                <option value="{{ $item->slug }}" @selected($filters['category']?->id === $item->id)>{{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
</form>

@if ($current)
<div class="stage">
    <div>
        <x-player :video="$current" :stage="true"/>
        <div class="meta-card">
            <h1 data-tv-title style="font-size:20px">{{ $current->title }}</h1>
            <span class="meta-card__loc" data-tv-location>
                {{ collect([$current->city?->displayName(), $current->country?->displayName()])->filter()->implode(', ') }}
            </span>
        </div>
    </div>
    <x-tv-panel :playlist="$playlist"/>
</div>
@else
    <div class="empty">{{ __('gtl.no_videos_yet') }}</div>
@endif
@endsection
