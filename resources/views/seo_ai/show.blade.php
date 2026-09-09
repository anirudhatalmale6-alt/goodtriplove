@extends('layouts.app')
@section('title', $translation->title)
@section('description', $translation->meta_description)
@section('canonical', route('seo-ai.show', ['locale' => $translation->locale, 'slug' => $translation->slug]))

@section('hreflang')
@foreach($page->translations as $alt)
<link rel="alternate" hreflang="{{ $alt->locale }}" href="{{ route('seo-ai.show', ['locale' => $alt->locale, 'slug' => $alt->slug]) }}">
@endforeach
@php($default = $page->translations->firstWhere('locale', config('goodtriplove.default_locale')) ?? $page->translations->first())
@if($default)<link rel="alternate" hreflang="x-default" href="{{ route('seo-ai.show', ['locale' => $default->locale, 'slug' => $default->slug]) }}">@endif
@endsection

@push('head')
@php
$articleSchema = ['@context'=>'https://schema.org','@type'=>'Article','headline'=>$translation->h1,'description'=>$translation->meta_description,'datePublished'=>optional($page->published_at)->toAtomString(),'dateModified'=>$page->updated_at->toAtomString(),'publisher'=>['@type'=>'Organization','name'=>'GoodTripLove','url'=>url('/')]];
$faq = collect($translation->faq_json ?? [])->map(fn($f)=>['@type'=>'Question','name'=>$f['question'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['answer']]])->values()->all();
@endphp
<script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@if($faq)<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$faq], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>@endif
@endpush

@section('content')
<article class="seo-article">
    <header class="hero">
        <span class="badge">GoodTripLove Guide</span>
        <h1>{{ $translation->h1 }}</h1>
        @if($translation->excerpt)<p class="lead">{{ $translation->excerpt }}</p>@endif
    </header>

    @foreach($translation->content_json ?? [] as $section)
        <section class="section">
            <h2>{{ $section['heading'] }}</h2>
            @foreach($section['paragraphs'] ?? [] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
        </section>
    @endforeach

    @if($places->isNotEmpty())
    <section class="section">
        <h2>{{ __('gtl.places') ?? 'Places' }}</h2>
        <div class="card-grid">
            @foreach($places as $place)
                <a class="card" href="{{ route('place.show', ['locale'=>app()->getLocale(),'country'=>$place->country->slug,'city'=>$place->city->slug,'place'=>$place->slug]) }}">
                    <div class="card-body"><strong>{{ $place->displayName() }}</strong><div class="muted">{{ $place->city?->displayName() }}</div></div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($videos->isNotEmpty())
    <section class="section">
        <h2>Videos</h2>
        <div class="video-grid">
            @foreach($videos as $video)
                <a class="video-card" href="{{ route('video.show', ['locale'=>app()->getLocale(),'video'=>$video->id]) }}">
                    <img src="{{ $video->thumbnail() }}" alt="{{ $video->title }}" loading="lazy">
                    <div class="video-card__body"><strong>{{ $video->title }}</strong></div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if(!empty($translation->faq_json))
    <section class="section">
        <h2>FAQ</h2>
        @foreach($translation->faq_json as $item)
            <details><summary>{{ $item['question'] }}</summary><p>{{ $item['answer'] }}</p></details>
        @endforeach
    </section>
    @endif
</article>
@endsection
