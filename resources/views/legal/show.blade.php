@extends('layouts.app')
@section('title', $document->title.' — GoodTripLove')
@section('description', Str::limit(strip_tags($document->content), 155))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ $document->title }} <span class="dot">.</span></h1>
    <a class="section__link" href="{{ route('legal.index') }}">{{ __('gtl.legal_centre') }} →</a>
</div>

<article class="meta-card" style="max-width:82ch;line-height:1.75">
    <p class="vcard__meta" style="margin:0 0 18px">
        {{ __('gtl.legal_version', ['version' => $document->version]) }}
        @if ($document->published_at) · {{ $document->published_at->isoFormat('LL') }} @endif
        @if ($document->locale !== app()->getLocale())
            · <span class="chip">{{ __('gtl.legal_fallback_language', ['language' => strtoupper($document->locale)]) }}</span>
        @endif
    </p>

    {!! $document->content !!}
</article>

<p style="margin-top:18px">
    <button class="btn btn--ghost btn--sm" type="button" data-cookie-settings>{{ __('gtl.manage_cookies') }}</button>
</p>
@endsection
