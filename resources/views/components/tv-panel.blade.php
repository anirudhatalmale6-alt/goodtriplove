@props(['playlist', 'title' => null])

@if ($playlist->isNotEmpty())
<aside class="tv" data-tv>
    <div class="tv__head">
        <h2 class="tv__title">GoodTripLove TV</h2>
        <span class="tv__live"><i></i>{{ __('gtl.live') }}</span>
        <span class="tv__toggle">
            {{ __('gtl.continuous_play') }}
            <button class="switch is-on" type="button" data-tv-continuous
                    aria-label="{{ __('gtl.continuous_play') }}"></button>
        </span>
    </div>

    <div class="tv__list">
        @foreach ($playlist as $index => $item)
            <button class="tv-item {{ $index === 0 ? 'is-current' : '' }}" type="button"
                    data-video-id="{{ $item->provider_video_id }}"
                    {{-- Carried per track: the stage copies these on click, and
                         without them switching track would replay whichever
                         video the stage was rendered with. --}}
                    data-embed-url="{{ $item->embedUrl() }}"
                    data-aspect="{{ $item->aspectRatio() }}"
                    data-play-url="{{ route('video.play', ['video' => $item->id]) }}"
                    data-title="{{ $item->title }}"
                    data-location="{{ collect([$item->city?->displayName(), $item->country?->displayName()])->filter()->implode(', ') }}">
                <span class="tv-item__thumb">
                    <img src="{{ $item->thumbnail() }}" alt="" loading="lazy" decoding="async" width="264" height="148">
                    @if ($item->durationForHumans())
                        <span class="tv-item__dur">{{ $item->durationForHumans() }}</span>
                    @endif
                </span>
                <span>
                    <span class="tv-item__title">{{ Str::limit($item->title, 62) }}</span>
                    <span class="tv-item__loc">{{ collect([$item->city?->displayName(), $item->country?->displayName()])->filter()->implode(', ') }}</span>
                </span>
                <span class="tv-item__more">
                    @if ($index === 0)
                        <svg class="tv-item__eq" width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="10" width="3" height="11" rx="1"/><rect x="10" y="4" width="3" height="17" rx="1"/><rect x="17" y="13" width="3" height="8" rx="1"/>
                        </svg>
                    @else
                        ⋮
                    @endif
                </span>
            </button>
        @endforeach
    </div>

    <div class="tv__footer">
        <a class="btn" href="{{ route('tv') }}">{{ __('gtl.see_more_videos') }} ⌄</a>
    </div>
</aside>
@endif
