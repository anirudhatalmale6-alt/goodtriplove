<?php

namespace App\Providers;

use App\Contracts\TotpVerifier;
use App\Support\Google2FaVerifier;
use App\Support\SystemSettings;
use Illuminate\Pagination\Paginator;
use App\View\Composers\SeoComposer;
use App\View\Composers\SiteComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TotpVerifier::class, Google2FaVerifier::class);
    }

    public function boot(): void
    {
        // The keys and switches an administrator saved override what the .env
        // says, before anything reads them. This is what makes the Turnstile,
        // YouTube and SMTP screens real: the services themselves are unchanged
        // and still read config(), they just get a different answer.
        SystemSettings::apply();

        Paginator::defaultView('pagination');
        Paginator::defaultSimpleView('pagination');

        // Behind DirectAdmin the app is reached over HTTPS; without this the
        // generated asset and form URLs come out as http:// and are blocked.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Without this the SEO overrides an administrator saves are never read
        // by any page. See SeoComposer.
        View::composer('layouts.app', SeoComposer::class);

        // Same reasoning for the editable site settings: the admin form is only
        // worth having if the values reach the page. See SiteComposer.
        View::composer('layouts.app', SiteComposer::class);
    }
}
