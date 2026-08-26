@props(['title', 'videos', 'link' => null, 'linkLabel' => null, 'columns' => 4])

@if ($videos->isNotEmpty())
<section class="section">
    <div class="section__head">
        <h2 class="section__title">{{ $title }} <span class="dot">.</span></h2>
        @if ($link)
            <a class="section__link" href="{{ $link }}">{{ $linkLabel ?? __('gtl.see_all') }} →</a>
        @endif
    </div>
    <div class="grid grid--{{ $columns }}">
        @foreach ($videos as $video)
            <x-video-card :video="$video"/>
        @endforeach
    </div>
</section>
@endif
