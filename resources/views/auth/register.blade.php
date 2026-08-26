@extends('layouts.app')
@section('title', __('gtl.register_free'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.register_title') }}</h1>
    <p class="sub">{{ __('gtl.register_sub') }}</p>

    <form method="post" action="{{ route('register') }}">
        @csrf
        <div class="field">
            <label for="name">{{ __('gtl.name') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
        </div>
        <div class="field">
            <label for="email">{{ __('gtl.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div class="field">
            <label for="account_type">{{ __('gtl.account_type') }}</label>
            <select id="account_type" name="account_type" required>
                <option value="user" @selected(old('account_type') !== 'business')>{{ __('gtl.account_traveller') }}</option>
                <option value="business" @selected(old('account_type') === 'business')>{{ __('gtl.account_business') }}</option>
            </select>
        </div>
        <div class="field">
            <label for="password">{{ __('gtl.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <span style="font-size:11.5px;color:var(--muted-2)">{{ __('gtl.password_rule') }}</span>
        </div>
        <div class="field">
            <label for="password_confirmation">{{ __('gtl.password_confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:12.5px;color:var(--muted);margin-bottom:16px">
            <input type="checkbox" name="terms" value="1" required> {{ __('gtl.accept_terms') }}
        </label>

        <x-turnstile/>

        <button class="btn btn--primary btn--block" type="submit" style="margin-top:16px">{{ __('gtl.register_free') }}</button>
    </form>

    <p style="margin-top:16px;font-size:13px;color:var(--muted)">
        {{ __('gtl.already_registered') }} <a href="{{ route('login') }}">{{ __('gtl.login') }}</a>
    </p>
</div>
@endsection
