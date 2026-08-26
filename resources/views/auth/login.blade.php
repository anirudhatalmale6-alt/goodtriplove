@extends('layouts.app')
@section('title', __('gtl.login'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.login_title') }}</h1>
    <p class="sub">{{ __('gtl.login_sub') }}</p>

    <form method="post" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">{{ __('gtl.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div class="field">
            <label for="password">{{ __('gtl.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <label style="display:flex;gap:8px;align-items:center;font-size:13px;color:var(--muted);margin-bottom:16px">
            <input type="checkbox" name="remember" value="1"> {{ __('gtl.remember_me') }}
        </label>

        <x-turnstile/>

        <button class="btn btn--primary btn--block" type="submit" style="margin-top:16px">{{ __('gtl.login') }}</button>
    </form>

    <p style="margin-top:16px;font-size:13px;color:var(--muted)">
        <a href="{{ route('password.request') }}">{{ __('gtl.forgot_password') }}</a> ·
        <a href="{{ route('register') }}">{{ __('gtl.register_free') }}</a>
    </p>
</div>
@endsection
