<?php

namespace App\Providers;

use App\Contracts\TotpVerifier;
use App\Support\Google2FaVerifier;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
    }
}
