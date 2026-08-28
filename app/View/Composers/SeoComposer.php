<?php

namespace App\View\Composers;

use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Makes the stored SEO overrides actually reach the page.
 *
 * The overrides table and SeoService already existed, but nothing ever called
 * the service — so every value an administrator could have saved was inert. A
 * settings screen for a value no page reads is worse than no screen at all,
 * because it looks like it works.
 *
 * Each page's own @section stays the default; an override, when one exists for
 * this route and locale, wins.
 */
class SeoComposer
{
    public function __construct(private SeoService $seo, private Request $request) {}

    public function compose(View $view): void
    {
        $view->with('seo', $this->resolve());
    }

    /**
     * @return array{title: ?string, description: ?string, canonical_url: ?string, indexable: bool, structured_data: mixed}
     */
    private function resolve(): array
    {
        $route = $this->request->route();

        $empty = ['title' => null, 'description' => null, 'canonical_url' => null,
            'indexable' => true, 'structured_data' => null];

        if (! $route) {
            return $empty;
        }

        return $this->seo->forPage(
            self::pageType($route->getName()),
            self::pageKey($route->parameters()),
            app()->getLocale(),
            $empty
        );
    }

    /**
     * The route name without its locale prefix, e.g. "city.show".
     */
    public static function pageType(?string $routeName): string
    {
        return $routeName ?: 'unknown';
    }

    /**
     * The identifying parameter of the page, so /city/portugal/lisbon and
     * /city/portugal/porto can carry different metadata. Pages with no
     * parameter share the key "*".
     *
     * The locale is deliberately excluded — it is a separate column, and
     * including it here would make every translation a different page.
     */
    public static function pageKey(array $parameters): string
    {
        $parts = [];

        foreach ($parameters as $name => $value) {
            if ($name === 'locale') {
                continue;
            }

            $parts[] = is_object($value)
                ? (string) ($value->slug ?? $value->getKey())
                : (string) $value;
        }

        return $parts === [] ? '*' : implode('/', $parts);
    }
}
