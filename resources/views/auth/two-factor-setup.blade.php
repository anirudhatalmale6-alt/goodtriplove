@extends('layouts.app')
@section('title', __('gtl.two_factor_setup'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.two_factor_setup') }}</h1>
    <p class="sub">{{ __('gtl.two_factor_setup_sub') }}</p>

    <div style="background:#fff;border-radius:14px;padding:14px;display:grid;place-items:center;margin-bottom:16px">
        {!! $qr !!}
    </div>

    <p class="sub" style="margin-bottom:6px">{{ __('gtl.two_factor_manual_key') }}</p>
    <p style="font-family:ui-monospace,Menlo,monospace;letter-spacing:2px;background:var(--panel-2);border:1px solid var(--line);border-radius:10px;padding:10px 12px;word-break:break-all;margin:0 0 18px">{{ $secret }}</p>

    <form method="post" action="{{ route('security.2fa.confirm') }}">
        @csrf
        <div class="field">
            <label for="code">{{ __('gtl.two_factor_code') }}</label>
            <input id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9]{6}"
                   maxlength="6" autocomplete="one-time-code" required autofocus>
        </div>
        <button class="btn btn--primary btn--block" type="submit">{{ __('gtl.two_factor_activate') }}</button>
    </form>
</div>
@endsection
