{{-- Accept / Reject / Customize, all equally reachable. Refusing never blocks
     browsing; the choice is stored with the policy version as evidence. --}}
<div class="cookie" data-cookie-banner hidden>
    <div class="cookie__inner">
        <div class="cookie__text">
            <strong>{{ __('gtl.cookie_title') }}</strong>
            <p>{{ __('gtl.cookie_intro') }}</p>
        </div>

        <div class="cookie__choices" data-cookie-choices hidden>
            <label class="cookie__row">
                <input type="checkbox" checked disabled> <span>{{ __('gtl.cookie_necessary') }}</span>
            </label>
            <label class="cookie__row">
                <input type="checkbox" data-consent="video"> <span>{{ __('gtl.cookie_video') }}</span>
            </label>
            <label class="cookie__row">
                <input type="checkbox" data-consent="analytics"> <span>{{ __('gtl.cookie_analytics') }}</span>
            </label>
        </div>

        <div class="cookie__actions">
            <button class="btn btn--ghost btn--sm" type="button" data-cookie-customize>{{ __('gtl.cookie_customize') }}</button>
            <button class="btn btn--ghost btn--sm" type="button" data-cookie-reject>{{ __('gtl.cookie_reject') }}</button>
            <button class="btn btn--primary btn--sm" type="button" data-cookie-accept>{{ __('gtl.cookie_accept') }}</button>
            <button class="btn btn--primary btn--sm" type="button" data-cookie-save hidden>{{ __('gtl.cookie_save') }}</button>
        </div>

        <a class="cookie__link" href="{{ route('legal.show', ['key' => 'cookies']) }}">{{ __('gtl.legal_cookies') }}</a>
    </div>
</div>
