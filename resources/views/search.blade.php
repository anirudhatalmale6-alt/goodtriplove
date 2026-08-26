@extends('layouts.app')
@section('title', $term ? __('gtl.meta_search_title', ['term' => $term]) : __('gtl.search'))
@push('head')<meta name="robots" content="noindex,follow">@endpush
@section('content')
<div class="section__head" style="margin-bottom:16px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.search') }} <span class="dot">.</span></h1>
</div>

<form class="filters" method="get">
    <div class="field" style="flex:1;min-width:230px">
        <label for="q">{{ __('gtl.search_placeholder') }}</label>
        <input id="q" name="q" type="search" value="{{ $term }}" autofocus>
    </div>
    <div class="field">
        <label for="country">{{ __('gtl.country') }}</label>
        <select id="country" name="country">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($countries as $item)
                <option value="{{ $item->slug }}" @selected($country?->id === $item->id)>{{ $item->flag_emoji }} {{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    @if ($cities->isNotEmpty())
    <div class="field">
        <label for="city">{{ __('gtl.city') }}</label>
        <select id="city" name="city">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($cities as $item)
                <option value="{{ $item->slug }}" @selected($city?->id === $item->id)>{{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="field">
        <label for="category">{{ __('gtl.category') }}</label>
        <select id="category" name="category">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($categories as $item)
                <option value="{{ $item->slug }}" @selected($category?->id === $item->id)>{{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field" style="min-width:auto">
        <label>&nbsp;</label>
        <button class="btn btn--primary" type="submit">{{ __('gtl.explore') }}</button>
    </div>
</form>

@if ($places->isNotEmpty())
<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.section_places') }} <span class="dot">.</span></h2></div>
    <div class="grid grid--6">
        @foreach ($places as $place)
            <a class="cat" href="{{ route('place.show', ['country' => $place->city->country_id ? \App\Models\Country::find($place->country_id)->slug : '', 'city' => $place->city->slug, 'place' => $place->slug]) }}">
                <div class="cat__name">{{ $place->name }}</div>
                <div class="cat__count">{{ $place->city?->displayName() }}</div>
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($videos instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $videos->total() > 0)
<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.videos') }} <span class="dot">.</span> <span style="color:var(--muted-2);font-size:14px;font-weight:600">{{ $videos->total() }}</span></h2></div>
    <div class="grid grid--5">
        @foreach ($videos as $video)<x-video-card :video="$video"/>@endforeach
    </div>
    <div class="pagination">{{ $videos->onEachSide(1)->links('pagination') }}</div>
</section>
@elseif ($term !== '')
    <div class="empty">{{ __('gtl.no_results', ['term' => $term]) }}</div>
@endif
@endsection
