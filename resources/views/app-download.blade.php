@extends('layouts.app')
@section('title', __('gtl.app_title'))
@section('description', __('gtl.app_pitch'))
@section('content')
<x-app-banner :release="$android"/>

<section class="section">
    <div class="section__head"><h2 class="section__title">{{ __('gtl.app_file_info') }} <span class="dot">.</span></h2></div>
    <div class="meta-card">
        <div class="facts">
            <div class="fact"><span class="fact__k">{{ __('gtl.version') }}</span><span class="fact__v">{{ $android->version ?? '—' }}</span></div>
            <div class="fact"><span class="fact__k">{{ __('gtl.app_updated') }}</span><span class="fact__v">{{ $android?->released_at?->isoFormat('LL') ?? '—' }}</span></div>
            <div class="fact"><span class="fact__k">{{ __('gtl.app_size') }}</span><span class="fact__v">{{ $android?->sizeForHumans() ?? '—' }}</span></div>
            <div class="fact"><span class="fact__k">iOS</span><span class="fact__v">{{ $ios?->store_url ? __('gtl.app_available') : __('gtl.app_ios_soon') }}</span></div>
        </div>
        @if ($android?->sha256)
            <p class="app-banner__hash" style="margin-top:14px;max-width:none">SHA-256 · {{ $android->sha256 }}</p>
        @endif
        <p class="meta-desc" style="margin-top:12px">{{ __('gtl.app_verify_notice') }}</p>
    </div>
</section>
@endsection
