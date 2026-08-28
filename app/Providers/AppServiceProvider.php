<?php

namespace App\Providers;

use App\Contracts\TotpVerifier;
use App\Support\Google2FaVerifier;
use Illuminate\Pagination\Paginator;
use App\View\Composers\SeoComposer;
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
    }
}
