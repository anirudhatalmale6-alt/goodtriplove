@props(['video', 'showCategory' => true])

{{-- A card is a thumbnail and nothing more: no iframe, no player, no YouTube
     script. The real player is only created on the video page or in TV. --}}
<a class="vcard" href="{{ route('video.show', ['video' => $video->id]) }}">
    <div class="vcard__thumb">
        <img src="{{ $video->thumbnail() }}" alt="{{ $video->title }}" loading="lazy" decoding="async"
             width="480" height="270">
        @if ($showCategory && $video->category)
            <span class="vcard__cat">{{ $video->category->displayName() }}</span>
        @endif
        @if ($video->durationForHumans())
            <span class="vcard__dur">{{ $video->durationForHumans() }}</span>
        @endif
        <span class="vcard__play" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        </span>
    </div>
    <div class="vcard__body">
        <p class="vcard__title">{{ $video->title }}</p>
        <p class="vcard__meta">
            {{ collect([$video->city?->displayName(), $video->country?->displayName()])->filter()->implode(', ') }}
        </p>
        <p class="vcard__stats">
            <span>{{ \App\Support\Format::compact($video->view_count) }} {{ __('gtl.views') }}</span>
            @if ($video->gtl_views > 0)
                <span>· {{ \App\Support\Format::compact($video->gtl_views) }} {{ __('gtl.views_gtl') }}</span>
            @endif
        </p>
    </div>
</a>
