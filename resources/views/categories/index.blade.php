@extends('layouts.app')
@section('title', __('gtl.meta_categories_title'))
@section('content')
<div class="section__head" style="margin-bottom:18px">
    <h1 class="section__title" style="font-size:26px">{{ __('gtl.nav_categories') }} <span class="dot">.</span></h1>
</div>
<div class="cat-rail">
    @foreach ($categories as $category)
        <a class="cat" href="{{ route('category.show', ['category' => $category->slug]) }}">
            <div class="cat__icon" style="font-size:30px">{{ $category->icon ?? '📍' }}</div>
            <div class="cat__name">{{ $category->displayName() }}</div>
            <div class="cat__count">{{ $category->videos_count }} {{ __('gtl.videos') }}</div>
        </a>
    @endforeach
</div>
@endsection
