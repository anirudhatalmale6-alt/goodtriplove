@extends('layouts.app')
@section('title', __('gtl.reset_password'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<div class="auth-card">
    <h1>{{ __('gtl.reset_password') }}</h1>
    <form method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="email">{{ __('gtl.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" required>
        </div>
        <div class="field">
            <label for="password">{{ __('gtl.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">{{ __('gtl.password_confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
        <button class="btn btn--primary btn--block" type="submit">{{ __('gtl.reset_password') }}</button>
    </form>
</div>
@endsection
