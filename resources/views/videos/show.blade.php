@extends('layouts.app')

@section('title', $video->title.' — GoodTripLove')
@section('description', Str::limit($video->description ?: $video->title, 155))
@section('og_type', 'video.other')
@section('og_image', $video->thumbnail())

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
    'name' => $video->title,
    'description' => Str::limit((string) $video->description, 300),
    'thumbnailUrl' => $video->thumbnail(),
    'uploadDate' => $video->published_at?->toIso8601String(),
    'embedUrl' => 'https://www.youtube.com/embed/'.$video->provider_video_id,
    'contentUrl' => $video->watchUrl(),
    'publisher' => ['@type' => 'Organization', 'name' => 'GoodTripLove'],
    'interactionStatistic' => [
        '@type' => 'InteractionCounter',
        'interactionType' => 'https://schema.org/WatchAction',
        'userInteractionCount' => $video->view_count,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<div class="stage">
    <div>
        <x-player :video="$video" :stage="true"/>

        <div class="meta-card">
            <div class="meta-card__top">
                <div>
                    <h1 data-tv-title>{{ $video->title }}</h1>
                    <span class="meta-card__loc" data-tv-location>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                            <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>
                        </svg>
                        {{ collect([$video->city?->displayName(), $video->country?->displayName()])->filter()->implode(', ') }}
                    </span>
                </div>

                <div class="meta-card__stats">
                    <div>
                        <div class="stat__label">{{ __('gtl.views_gtl_label') }}</div>
                        <div class="stat__value" data-gtl-views="{{ $video->provider_video_id }}">{{ \App\Support\Format::number($video->gtl_views) }}</div>
                    </div>
                    <div>
                        <div class="stat__label">{{ __('gtl.views_youtube_label') }}</div>
                        <div class="stat__value">{{ \App\Support\Format::compact($video->view_count) }}</div>
                    </div>
                </div>

                <div class="meta-card__acts">
                    <button class="btn btn--ghost btn--sm" type="button"
                            data-favorite="video" data-id="{{ $video->id }}"
                            data-guest="{{ auth()->check() ? '0' : '1' }}"
                            data-login-url="{{ route('login') }}"
                            data-url="{{ route('favorite.toggle') }}">♥ {{ __('gtl.add_to_favorites') }}</button>
                    <a class="btn btn--ghost btn--sm" href="{{ $video->watchUrl() }}" target="_blank" rel="noopener nofollow">↗ {{ __('gtl.share') }}</a>
                </div>
            </div>

            <div class="chips">
                @if ($video->category)<span class="chip chip--accent">{{ $video->category->displayName() }}</span>@endif
                @if ($video->subcategory)<span class="chip">{{ $video->subcategory->displayName() }}</span>@endif
                @if ($video->country)<span class="chip chip--violet">#{{ $video->country->displayName() }}</span>@endif
            </div>

            @if ($video->description)
                <p class="meta-desc">{{ Str::limit($video->description, 400) }}</p>
            @endif

            <div data-tabs class="tabs">
                <button data-tab="tab-info" class="is-active">ⓘ {{ __('gtl.tab_information') }}</button>
                @if ($place)<button data-tab="tab-place">📍 {{ __('gtl.tab_place') }}</button>@endif
                <button data-tab="tab-report">⚑ {{ __('gtl.report') }}</button>
            </div>

            <div id="tab-info" class="tab-panel is-active">
                <div class="facts">
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_place') }}</span><span class="fact__v">{{ $video->city?->displayName() ?? '—' }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_country') }}</span><span class="fact__v">{{ $video->country?->flag_emoji }} {{ $video->country?->displayName() ?? '—' }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_category') }}</span><span class="fact__v">{{ $video->category?->displayName() ?? '—' }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_added') }}</span><span class="fact__v">{{ $video->created_at?->isoFormat('LL') }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_source') }}</span><span class="fact__v">YouTube</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_channel') }}</span><span class="fact__v">{{ $video->channel_title ?? '—' }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_language') }}</span><span class="fact__v">{{ $video->language ? strtoupper($video->language) : '—' }}</span></div>
                    <div class="fact"><span class="fact__k">{{ __('gtl.fact_duration') }}</span><span class="fact__v">{{ $video->durationForHumans() ?? '—' }}</span></div>
                </div>
            </div>

            @if ($place)
            <div id="tab-place" class="tab-panel">
                <h3 style="margin:0 0 8px">{{ $place->name }}</h3>
                <p class="meta-desc">{{ $place->address }}</p>
                @if ($place->latitude && $place->longitude)
                    <div class="map-box" style="margin-top:12px">
                        <iframe loading="lazy" title="{{ $place->name }}"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $place->longitude - 0.01 }}%2C{{ $place->latitude - 0.01 }}%2C{{ $place->longitude + 0.01 }}%2C{{ $place->latitude + 0.01 }}&amp;layer=mapnik&amp;marker={{ $place->latitude }}%2C{{ $place->longitude }}"></iframe>
                    </div>
                @endif
                <p style="margin-top:12px">
                    <a class="btn btn--primary btn--sm" href="{{ route('place.show', ['country' => $place->city->country->slug, 'city' => $place->city->slug, 'place' => $place->slug]) }}">
                        {{ __('gtl.see_place_page') }}
                    </a>
                </p>
            </div>
            @endif

            <div id="tab-report" class="tab-panel">
                <form action="{{ route('content.report') }}" method="post" style="max-width:520px">
                    @csrf
                    <input type="hidden" name="target_type" value="video">
                    <input type="hidden" name="target_id" value="{{ $video->id }}">
                    <input type="hidden" name="target_url" value="{{ url()->current() }}">
                    <div class="field">
                        <label for="reason">{{ __('gtl.report_reason') }}</label>
                        <select id="reason" name="reason" required>
                            @foreach (['wrong_place', 'unavailable', 'copyright', 'inappropriate', 'personal_data', 'other'] as $reason)
                                <option value="{{ $reason }}">{{ __('gtl.report_reason_'.$reason) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="explanation">{{ __('gtl.report_explanation') }}</label>
                        <textarea id="explanation" name="explanation" rows="3" maxlength="5000" required></textarea>
                    </div>
                    <div class="field">
                        <label for="reporter_email">{{ __('gtl.report_email') }}</label>
                        <input id="reporter_email" name="reporter_email" type="email" value="{{ auth()->user()?->email }}">
                    </div>
                    <button class="btn btn--primary btn--sm" type="submit">{{ __('gtl.report_send') }}</button>
                    <a class="btn btn--ghost btn--sm" href="{{ route('legal.show', ['key' => 'content-reporting']) }}">{{ __('gtl.legal_content_reporting') }}</a>
                </form>
            </div>
        </div>
    </div>

    <x-tv-panel :playlist="$playlist"/>
</div>

<x-video-section :title="__('gtl.section_similar')" :videos="$similar" :columns="4"/>
@endsection
