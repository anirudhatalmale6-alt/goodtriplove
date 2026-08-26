@if (config('security.turnstile.site_key'))
    <div class="cf-turnstile" data-sitekey="{{ config('security.turnstile.site_key') }}"
         data-theme="dark" data-language="{{ app()->getLocale() }}"></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
