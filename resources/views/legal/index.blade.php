@extends('layouts.app')
@section('title', __('gtl.legal_centre'))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.legal_centre') }} <span class="dot">.</span></h1>
</div>

<div class="grid grid--3">
    @foreach (\App\Http\Controllers\LegalController::KEYS as $key)
        <a class="meta-card" href="{{ route('legal.show', ['key' => $key]) }}" style="display:block">
            <h2 style="font-size:17px;margin:0 0 6px">{{ __('gtl.legal_'.str_replace('-', '_', $key)) }}</h2>
            <p class="vcard__meta" style="margin:0">{{ __('gtl.legal_'.str_replace('-', '_', $key).'_hint') }}</p>
        </a>
    @endforeach
</div>

<div class="meta-card" style="margin-top:22px">
    <h2 style="font-size:17px;margin:0 0 8px">{{ __('gtl.report_content_title') }}</h2>
    <p class="meta-desc">{{ __('gtl.report_content_intro') }}</p>
    <p style="margin-top:12px">
        <a class="btn btn--primary btn--sm" href="{{ route('legal.report') }}">{{ __('gtl.report_content_cta') }}</a>
        <button class="btn btn--ghost btn--sm" type="button" data-cookie-settings>{{ __('gtl.manage_cookies') }}</button>
    </p>
</div>
@endsection
