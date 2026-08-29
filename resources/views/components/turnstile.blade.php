{{--
    The widget and the middleware must agree, or the site breaks in one of two
    ways: a widget nobody checks, or — much worse — a check with no widget to
    answer it, which rejects every visitor. Both ask SystemSettings the same
    question, so they cannot drift apart.
--}}
@if (\App\Support\SystemSettings::turnstileActive())
    <div class="cf-turnstile" data-sitekey="{{ config('security.turnstile.site_key') }}"
         data-theme="dark" data-language="{{ app()->getLocale() }}"></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
