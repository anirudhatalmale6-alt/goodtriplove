@extends('layouts.app')
@section('title', __('gtl.forgot_password'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.forgot_password') }}</h1>
    <p class="sub">{{ __('gtl.forgot_password_sub') }}</p>
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">{{ __('gtl.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
        </div>
        <x-turnstile/>
        <button class="btn btn--primary btn--block" type="submit" style="margin-top:16px">{{ __('gtl.send_link') }}</button>
    </form>
</div>
@endsection
