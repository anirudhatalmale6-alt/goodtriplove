@extends('layouts.app')
@section('title', $category->displayName().' — GoodTripLove')
@section('description', Str::limit($category->describe() ?: __('gtl.meta_category_fallback', ['category' => $category->displayName()]), 155))
@section('content')
<div class="section__head" style="margin-bottom:16px">
    <h1 class="section__title" style="font-size:26px">{{ $category->icon }} {{ $category->displayName() }} <span class="dot">.</span></h1>
</div>

<form class="filters" method="get" style="margin-bottom:18px">
    <div class="field">
        <label for="country">{{ __('gtl.country') }}</label>
        <select id="country" name="country" onchange="this.form.submit()">
            <option value="">{{ __('gtl.all') }}</option>
            @foreach ($countries as $item)
                <option value="{{ $item->slug }}" @selected($country?->id === $item->id)>{{ $item->flag_emoji }} {{ $item->displayName() }}</option>
            @endforeach
        </select>
    </div>
</form>

@if ($featured)
<div class="stage">
    <div><x-player :video="$featured" :stage="true"/></div>
    <x-tv-panel :playlist="$playlist"/>
</div>
@endif

@php $titles = ['popular'=>'section_most_popular','most_viewed'=>'section_most_viewed','trending'=>'section_trending','relevant'=>'section_most_relevant','recent'=>'section_recent']; @endphp
@foreach ($sections as $key => $videos)
    <x-video-section :title="__('gtl.'.$titles[$key])" :videos="$videos" :columns="4"/>
    @if ($loop->first && $ads->isNotEmpty())<x-ad-slot :ad="$ads->first()"/>@endif
@endforeach
@endsection
