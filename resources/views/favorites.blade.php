@extends('layouts.app')
@section('title', __('gtl.favorites'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.favorites') }} <span class="dot">.</span></h1>
</div>
@if ($favorites->total() > 0)
    <div class="grid grid--5">
        @foreach ($favorites as $favorite)
            @if ($favorite->favoritable instanceof \App\Models\Video)
                <x-video-card :video="$favorite->favoritable"/>
            @elseif ($favorite->favoritable)
                <div class="cat">
                    <div class="cat__name">{{ $favorite->favoritable->name }}</div>
                    <div class="cat__count">{{ $favorite->favoritable->city?->displayName() }}</div>
                </div>
            @endif
        @endforeach
    </div>
    <div class="pagination">{{ $favorites->links('pagination') }}</div>
@else
    <div class="empty">{{ __('gtl.no_favorites') }}</div>
@endif
@endsection
