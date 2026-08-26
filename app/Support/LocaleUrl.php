<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Rewrites the current URL into another language.
 *
 * Only the locale segment changes — the rest of the path and the query string
 * are preserved, so the switcher lands on the same page rather than sending
 * everyone back to the homepage.
 */
class LocaleUrl
{
    /**
     * Best language for this request: URL segment, then the signed-in user's
     * preference, then the session, then Accept-Language, then the default.
     */
    public static function resolve(?\Illuminate\Http\Request $request = null): string
    {
        $request ??= request();
        $supported = array_keys(config('goodtriplove.locales'));
        $default = config('goodtriplove.default_locale', 'fr');

        foreach ([
            $request->route('locale'),
            $request->user()?->locale,
            $request->session()?->get('locale'),
            $request->getPreferredLanguage($supported),
        ] as $candidate) {
            if (in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $default;
    }

    public static function current(string $locale): string
    {
        $supported = array_keys(config('goodtriplove.locales'));

        if (! in_array($locale, $supported, true)) {
            $locale = config('goodtriplove.default_locale');
        }

        $segments = Request::segments();

        if (isset($segments[0]) && in_array($segments[0], $supported, true)) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $query = Request::getQueryString();

        return url(implode('/', $segments)).($query ? '?'.$query : '');
    }

    /**
     * The same route in every language — used to build hreflang blocks on
     * pages that know their own route name and parameters.
     */
    public static function alternates(string $routeName, array $parameters = []): array
    {
        $urls = [];

        foreach (array_keys(config('goodtriplove.locales')) as $locale) {
            $urls[$locale] = route($routeName, array_merge($parameters, ['locale' => $locale]));
        }

        return $urls;
    }
}
