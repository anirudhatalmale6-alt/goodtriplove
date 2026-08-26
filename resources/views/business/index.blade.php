@extends('layouts.app')
@section('title', __('gtl.my_space'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.my_places') }} <span class="dot">.</span></h1>
    <a class="btn btn--primary btn--sm" href="{{ route('business.create') }}">+ {{ __('gtl.add_your_place') }}</a>
</div>

@if ($places->isEmpty())
    <div class="empty">
        <p style="margin:0 0 14px">{{ __('gtl.no_places_yet') }}</p>
        <a class="btn btn--primary" href="{{ route('business.create') }}">{{ __('gtl.add_your_place') }}</a>
    </div>
@else
    <div class="grid grid--3">
        @foreach ($places as $place)
            <div class="meta-card">
                <h3 style="margin:0 0 6px;font-size:17px">{{ $place->name }}</h3>
                <p class="vcard__meta">{{ $place->city?->displayName() }} · {{ $place->category?->displayName() }}</p>
                <div class="chips">
                    @if ($place->status === 'published')
                        <span class="chip chip--green">{{ __('gtl.status_published') }}</span>
                    @elseif ($place->status === 'rejected')
                        <span class="chip chip--accent">{{ __('gtl.status_rejected') }}</span>
                    @else
                        <span class="chip">{{ __('gtl.status_pending') }}</span>
                    @endif
                    <span class="chip">{{ $place->videos_count }} {{ __('gtl.videos') }}</span>
                </div>
                @if ($place->rejection_reason)
                    <p class="meta-desc" style="color:#ff9db0">{{ $place->rejection_reason }}</p>
                @endif

                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                    <a class="btn btn--ghost btn--sm" href="{{ route('business.edit', ['place' => $place->id]) }}">{{ __('gtl.edit') }}</a>
                    @if ($place->status === 'published' && $place->city)
                        <a class="btn btn--ghost btn--sm" href="{{ route('place.show', ['country' => $place->city->country->slug, 'city' => $place->city->slug, 'place' => $place->slug]) }}">{{ __('gtl.view') }}</a>
                    @endif
                </div>

                <form action="{{ route('business.video', ['place' => $place->id]) }}" method="post" style="margin-top:12px">
                    @csrf
                    <div class="field">
                        <label for="yt-{{ $place->id }}">{{ __('gtl.propose_video') }}</label>
                        <input id="yt-{{ $place->id }}" name="youtube_url" placeholder="https://www.youtube.com/watch?v=…">
                    </div>
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('gtl.submit') }}</button>
                </form>
            </div>
        @endforeach
    </div>
@endif
@endsection
