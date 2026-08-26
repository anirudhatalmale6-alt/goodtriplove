@extends('layouts.app')
@section('title', $place->exists ? __('gtl.edit_place') : __('gtl.add_your_place'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card" style="width:min(740px,100%)">
    <h1>{{ $place->exists ? __('gtl.edit_place') : __('gtl.add_your_place') }}</h1>
    <p class="sub">{{ __('gtl.business_free_notice') }}</p>

    <form method="post" action="{{ $place->exists ? route('business.update', ['place' => $place->id]) : route('business.store') }}">
        @csrf
        @if ($place->exists) @method('PUT') @endif

        <div class="field">
            <label for="name">{{ __('gtl.place_name') }}</label>
            <input id="name" name="name" value="{{ old('name', $place->name) }}" required maxlength="150">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field">
                <label for="country_id">{{ __('gtl.country') }}</label>
                <select id="country_id" data-cities-for="{{ url('/admin/countries/__ID__/cities') }}" data-cities-target="#city_id">
                    <option value="">—</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}" @selected($place->country_id === $country->id)>{{ $country->flag_emoji }} {{ $country->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="city_id">{{ __('gtl.city') }}</label>
                <select id="city_id" name="city_id" required>
                    <option value="">—</option>
                    @foreach (\App\Models\City::active()->when($place->country_id, fn($q) => $q->where('country_id', $place->country_id))->orderBy('slug')->get() as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id', $place->city_id) == $city->id)>{{ $city->displayName() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field">
                <label for="category_id">{{ __('gtl.category') }}</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $place->category_id) == $category->id)>{{ $category->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="price_level">{{ __('gtl.price_level') }}</label>
                <select id="price_level" name="price_level">
                    <option value="">—</option>
                    @for ($i = 1; $i <= 4; $i++)
                        <option value="{{ $i }}" @selected(old('price_level', $place->price_level) == $i)>{{ str_repeat('€', $i) }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="field">
            <label for="description">{{ __('gtl.description') }} ({{ strtoupper(app()->getLocale()) }})</label>
            <textarea id="description" name="description[{{ app()->getLocale() }}]" rows="4" maxlength="3000">{{ old('description.'.app()->getLocale(), $place->describe()) }}</textarea>
        </div>

        <div class="field">
            <label for="address">{{ __('gtl.address') }}</label>
            <input id="address" name="address" value="{{ old('address', $place->address) }}" maxlength="255">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field">
                <label for="phone">{{ __('gtl.phone') }}</label>
                <input id="phone" name="phone" value="{{ old('phone', $place->phone) }}" maxlength="64">
            </div>
            <div class="field">
                <label for="website">{{ __('gtl.website') }}</label>
                <input id="website" name="website" type="url" value="{{ old('website', $place->website) }}" maxlength="255">
            </div>
        </div>

        @if (! $place->exists)
            <x-turnstile/>
        @endif

        <button class="btn btn--primary btn--block" type="submit" style="margin-top:16px">
            {{ $place->exists ? __('gtl.save') : __('gtl.submit_for_review') }}
        </button>
        <p style="font-size:12px;color:var(--muted-2);margin-top:10px">{{ __('gtl.moderation_notice') }}</p>
    </form>
</div>
@endsection
