<?php

namespace App\Http\Middleware;

use App\Support\LocaleUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every public URL carries its language: /fr/…, /pt/…, /es/…
 * The segment wins; then the signed-in user's preference; then the session;
 * then Accept-Language; then the configured default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleUrl::resolve($request);

        App::setLocale($locale);
        $request->session()?->put('locale', $locale);

        // So route('place.show', $place) keeps the visitor in their language
        // without every call site passing the locale by hand.
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
