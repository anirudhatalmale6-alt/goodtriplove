@props(['video', 'overlay' => true, 'stage' => false])

{{-- Facade player. data-play swaps in the platform's iframe on click.

     The embed address is rendered as an attribute rather than built in
     JavaScript, so one place decides how each platform embeds. Putting it in
     the markup contacts nobody: it is a string until the visitor clicks. --}}
<div class="player"
     @if ($stage) data-tv-stage @endif
     data-video-id="{{ $video->provider_video_id }}"
     data-provider="{{ $video->provider }}"
     data-embed-url="{{ $video->embedUrl() }}"
     data-aspect="{{ $video->aspectRatio() }}"
     data-play-url="{{ route('video.play', ['video' => $video->id]) }}"
     data-title="{{ $video->title }}">
    <img class="player__thumb {{ $video->hasRealThumbnail() ? '' : 'player__thumb--placeholder' }}"
         src="{{ $video->thumbnail() }}" alt="{{ $video->title }}"
         fetchpriority="high" decoding="async" width="1280" height="720">

    <div class="player__overlay">
        <div class="player__badges">
            <span class="tag tag--platform" style="--platform:{{ $video->platformColour() }}">
                @if ($video->provider === 'youtube')
                <svg width="15" height="11" viewBox="0 0 24 17" fill="currentColor" aria-hidden="true">
                    <path d="M23.5 2.7A3 3 0 0 0 21.4.6C19.5 0 12 0 12 0S4.5 0 2.6.6A3 3 0 0 0 .5 2.7 31 31 0 0 0 0 8.5c0 2 .2 4 .5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1c.3-1.9.5-3.8.5-5.8s-.2-4-.5-5.8z"/>
                </svg>
                @else
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                @endif
                {{ $video->platformLabel() }}
            </span>
            <div class="player__actions">
                <button class="tag tag--action" type="button"
                        data-favorite="video" data-id="{{ $video->id }}"
                        data-guest="{{ auth()->check() ? '0' : '1' }}"
                        data-login-url="{{ route('login') }}"
                        data-url="{{ route('favorite.toggle') }}">
                    ♥ {{ __('gtl.add_to_favorites') }}
                </button>
                @if ($video->isPlayable())
                <a class="tag tag--action" href="{{ $video->watchUrl() }}" target="_blank" rel="noopener nofollow">
                    ↗ {{ __('gtl.watch_on_platform', ['platform' => $video->platformLabel()]) }}
                </a>
                @endif
            </div>
        </div>

        @if ($overlay)
        <div class="player__title-block">
            <p class="player__kicker" data-tv-title-big>{{ Str::upper($video->city?->displayName() ?? $video->country?->displayName() ?? 'GoodTripLove') }}</p>
            <p class="player__sub" data-tv-title>{{ $video->title }}</p>
        </div>
        @endif
    </div>

    @if ($video->isPlayable())
        <button class="player__play" type="button" data-play aria-label="{{ __('gtl.play') }}">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        </button>
    @else
        <span class="player__play player__play--static" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        </span>
    @endif
</div>
