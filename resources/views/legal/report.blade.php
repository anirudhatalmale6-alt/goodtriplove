@extends('layouts.app')
@section('title', __('gtl.report_content_title'))
@section('content')
<div class="auth-card" style="width:min(680px,100%)">
    <h1>{{ __('gtl.report_content_title') }}</h1>
    <p class="sub">{{ __('gtl.report_content_intro') }}</p>

    <form method="post" action="{{ route('content.report') }}">
        @csrf
        <div class="field">
            <label for="target_url">{{ __('gtl.report_url') }}</label>
            <input id="target_url" name="target_url" type="url" value="{{ old('target_url', request('url')) }}"
                   placeholder="https://goodtriplove.com/fr/video/…">
        </div>

        <input type="hidden" name="target_type" value="{{ old('target_type', request('type', 'video')) }}">
        @if (request()->filled('id'))
            <input type="hidden" name="target_id" value="{{ (int) request('id') }}">
        @endif

        <div class="field">
            <label for="reason">{{ __('gtl.report_reason') }}</label>
            <select id="reason" name="reason" required>
                @foreach (['wrong_place', 'unavailable', 'copyright', 'inappropriate', 'personal_data', 'other'] as $reason)
                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ __('gtl.report_reason_'.$reason) }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="explanation">{{ __('gtl.report_explanation') }}</label>
            <textarea id="explanation" name="explanation" rows="5" maxlength="5000" required>{{ old('explanation') }}</textarea>
        </div>

        <div class="field">
            <label for="reporter_email">{{ __('gtl.report_email') }}</label>
            <input id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email', auth()->user()?->email) }}">
            <span style="font-size:11.5px;color:var(--muted-2)">{{ __('gtl.report_email_hint') }}</span>
        </div>

        <label style="display:flex;gap:8px;align-items:flex-start;font-size:12.5px;color:var(--muted);margin-bottom:16px">
            <input type="checkbox" required> {{ __('gtl.report_good_faith') }}
        </label>

        <button class="btn btn--primary btn--block" type="submit">{{ __('gtl.report_send') }}</button>
    </form>
</div>
@endsection
