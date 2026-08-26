@extends('layouts.app')

@section('title', __('gtl.meta_videos_title'))
@section('description', __('gtl.meta_videos_description'))

@section('content')
<div class="section__head" style="margin-bottom:16px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.nav_top_videos') }} <span class="dot">.</span></h1>
</div>

<form class="filters" method="get">
    <div class="field">
        <label for="country">{{ __('gtl.country') }}</label>
        <select id="country" name="country" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($countries as $item)
                <option value="{{ $item->slug }}" @selected($country?->id === $item->id)>{{ $item->flag_emoji }} {{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="category">{{ __('gtl.category') }}</label>
        <select id="category" name="category" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($categories as $item)
                <option value="{{ $item->slug }}" @selected($category?->id === $item->id)>{{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="sort" value="{{ $section }}">
</form>

<div class="sort-tabs" style="margin:16px 0">
    @foreach (['popular' => 'sort_popular', 'most_viewed' => 'sort_most_viewed', 'trending' => 'sort_trending', 'relevant' => 'sort_relevant', 'recent' => 'sort_recent'] as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['sort' => $key]) }}" class="{{ $section === $key ? 'is-active' : '' }}">{{ __('gtl.'.$label) }}</a>
    @endforeach
</div>

@if ($videos->total() > 0)
    <div class="grid grid--5">
        @foreach ($videos as $video)
            <x-video-card :video="$video"/>
        @endforeach
    </div>
    <div class="pagination">{{ $videos->onEachSide(1)->links('pagination') }}</div>
@else
    <div class="empty">{{ __('gtl.no_videos_yet') }}</div>
@endif
@endsection
