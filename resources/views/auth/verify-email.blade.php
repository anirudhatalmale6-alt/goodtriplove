@extends('layouts.app')
@section('title', __('gtl.verify_email_title'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.verify_email_title') }}</h1>
    <p class="sub">{{ __('gtl.verify_email_sub', ['email' => auth()->user()->email]) }}</p>

    <form method="post" action="{{ route('verification.verify') }}">
        @csrf
        <div class="field">
            <label for="code">{{ __('gtl.verify_code_label') }}</label>
            <input id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>
        <button class="btn btn--primary btn--block" type="submit">{{ __('gtl.verify') }}</button>
    </form>

    <form method="post" action="{{ route('verification.resend') }}" style="margin-top:14px">
        @csrf
        <button class="btn btn--ghost btn--block" type="submit">{{ __('gtl.resend_code') }}</button>
    </form>
</div>
@endsection
