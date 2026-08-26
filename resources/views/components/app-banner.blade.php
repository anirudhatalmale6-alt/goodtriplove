@props(['release' => null])

<section class="app-banner">
    <img class="app-banner__phone" src="{{ asset('img/app-phone.svg') }}" alt="" loading="lazy" decoding="async">

    <div>
        <h3>{{ __('gtl.app_title') }}</h3>
        <p>{{ __('gtl.app_pitch') }}</p>
        <div class="app-banner__checks">
            <span class="app-check">✓ {{ __('gtl.app_check_official') }}</span>
            <span class="app-check">✓ {{ __('gtl.app_check_signed') }}</span>
            <span class="app-check">✓ {{ __('gtl.app_check_https') }}</span>
            <span class="app-check">✓ {{ __('gtl.app_check_offline') }}</span>
        </div>
    </div>

    <div class="app-banner__cta">
        @if ($release && ($release->store_url || $release->file_path))
            <a class="btn btn--green" href="{{ route('app.android') }}">▸ {{ __('gtl.app_download_android') }}</a>
            <div class="app-banner__version">
                {{ __('gtl.version') }} {{ $release->version }}
                @if ($release->released_at) · {{ $release->released_at->isoFormat('LL') }} @endif
                @if ($release->sizeForHumans()) · {{ $release->sizeForHumans() }} @endif
            </div>
            @if ($release->sha256)
                <div class="app-banner__hash">SHA-256 {{ $release->sha256 }}</div>
            @endif
        @else
            <a class="btn btn--ghost" href="{{ route('app.download') }}">{{ __('gtl.app_coming_soon') }}</a>
            <div class="app-banner__version">{{ __('gtl.app_ios_soon') }}</div>
        @endif
    </div>
</section>
