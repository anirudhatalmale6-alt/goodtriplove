@props(['ad'])

@if ($ad)
<a class="ad-slot" href="{{ $ad->target_url ? route('ad.click', ['ad' => $ad->id]) : '#' }}"
   @if ($ad->target_url) rel="sponsored noopener" @endif
   @style([
     'background:'.$ad->background_color => $ad->background_color,
     'color:'.$ad->text_color => $ad->text_color,
   ])>
    <div>
        <div class="ad-slot__label">{{ __('gtl.sponsored') }}</div>
        <h3>{{ $ad->translate('title') ?? $ad->name }}</h3>
        @if ($ad->translate('subtitle'))
            <p>{{ $ad->translate('subtitle') }}</p>
        @endif
    </div>
    @if ($ad->image)
        <img src="{{ $ad->image }}" alt="" loading="lazy" decoding="async">
    @elseif ($ad->translate('cta_label'))
        <span class="btn btn--primary">{{ $ad->translate('cta_label') }}</span>
    @endif
</a>
@endif
