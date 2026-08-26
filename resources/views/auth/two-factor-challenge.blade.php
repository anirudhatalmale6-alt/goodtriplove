@extends('layouts.app')
@section('title', __('gtl.two_factor_challenge'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.two_factor_challenge') }}</h1>
    <p class="sub">{{ __('gtl.two_factor_challenge_sub') }}</p>

    <form method="post" action="{{ route('security.2fa.verify') }}">
        @csrf
        <div class="field">
            <label for="code">{{ __('gtl.two_factor_code') }}</label>
            <input id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>
        <button class="btn btn--primary btn--block" type="submit">{{ __('gtl.verify') }}</button>
    </form>
</div>
@endsection
