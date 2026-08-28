<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Arr;

/**
 * The list of things an administrator may change without a developer.
 *
 * Everything here is declared once and drives three places at the same time:
 * the admin form, the validation of what it posts, and the values the site
 * renders. Adding a row to DEFINITIONS is the whole job of making a new
 * setting editable — there is no second list to keep in step.
 *
 * A setting with no stored value falls back to its declared default, so the
 * site looks exactly the same on the day this ships as it did the day before.
 */
class SiteSettings
{
    /**
     * type:         text | textarea | email | url | tel | bool
     * translatable: one value per language, falling back to the default locale
     * default:      used until an administrator saves something
     * help:         shown under the field in the admin
     */
    public const DEFINITIONS = [
        'identity' => [
            'label' => 'Identité du site',
            'items' => [
                'site_name' => ['type' => 'text', 'label' => 'Nom du site', 'default' => 'GoodTripLove'],
                'tagline' => ['type' => 'text', 'label' => 'Slogan', 'translatable' => true, 'default' => ''],
                'footer_pitch' => ['type' => 'textarea', 'label' => 'Texte de présentation (pied de page)', 'translatable' => true, 'default' => ''],
            ],
        ],
        'contact' => [
            'label' => 'Coordonnées',
            'items' => [
                'contact_email' => ['type' => 'email', 'label' => 'Adresse email de contact', 'default' => ''],
                'contact_phone' => ['type' => 'tel', 'label' => 'Téléphone', 'default' => ''],
                'contact_address' => ['type' => 'textarea', 'label' => 'Adresse postale', 'default' => ''],
            ],
        ],
        'social' => [
            'label' => 'Réseaux sociaux',
            'items' => [
                'social_facebook' => ['type' => 'url', 'label' => 'Facebook', 'default' => ''],
                'social_instagram' => ['type' => 'url', 'label' => 'Instagram', 'default' => ''],
                'social_youtube' => ['type' => 'url', 'label' => 'YouTube', 'default' => ''],
                'social_tiktok' => ['type' => 'url', 'label' => 'TikTok', 'default' => ''],
            ],
        ],
    ];

    /** Every declared key, flattened. */
    public static function keys(): array
    {
        return array_merge(...array_map(
            fn (array $group) => array_keys($group['items']),
            array_values(self::DEFINITIONS),
        ));
    }

    public static function definition(string $key): ?array
    {
        foreach (self::DEFINITIONS as $group) {
            if (isset($group['items'][$key])) {
                return $group['items'][$key];
            }
        }

        return null;
    }

    public static function isTranslatable(string $key): bool
    {
        return (bool) (self::definition($key)['translatable'] ?? false);
    }

    /**
     * The value to render, for one language.
     *
     * A translatable setting is stored as a map of locale => text. An empty
     * translation falls back to the default locale rather than printing
     * nothing, which is what makes a half-translated site still readable.
     */
    public static function value(string $key, ?string $locale = null): mixed
    {
        $definition = self::definition($key);
        $stored = SiteSetting::get($key);

        if ($definition && ($definition['translatable'] ?? false)) {
            $locale ??= app()->getLocale();
            $map = is_array($stored) ? $stored : [];

            $value = Arr::get($map, $locale);

            // Fall back to the site's own default language, not Laravel's
            // `app.fallback_locale` — that one is still 'en' here, so a French
            // site with only French filled in would have shown nothing at all.
            if (! filled($value)) {
                $value = Arr::get($map, config('goodtriplove.default_locale', 'fr'));
            }

            // Still nothing: any language an administrator did fill in beats an
            // empty block on the page.
            if (! filled($value)) {
                $value = Arr::first($map, fn ($text) => filled($text));
            }

            return filled($value) ? $value : ($definition['default'] ?? '');
        }

        if (is_array($stored)) {
            // A translatable setting that later became a plain one would land
            // here; take something printable rather than an array to string.
            $stored = Arr::first($stored);
        }

        return filled($stored) ? $stored : ($definition['default'] ?? null);
    }

    /** All declared settings resolved for one language, for the view composer. */
    public static function all(?string $locale = null): array
    {
        $out = [];
        foreach (self::keys() as $key) {
            $out[$key] = self::value($key, $locale);
        }

        return $out;
    }

    /** The social links that are actually filled in, ready to render. */
    public static function socialLinks(): array
    {
        $out = [];

        foreach (self::DEFINITIONS['social']['items'] as $key => $definition) {
            $url = self::value($key);
            if (filled($url)) {
                $out[] = ['label' => $definition['label'], 'url' => $url, 'key' => $key];
            }
        }

        return $out;
    }

    /** Validation rules for the admin form, derived from the same declaration. */
    public static function rules(array $locales): array
    {
        $rules = [];

        foreach (self::DEFINITIONS as $group) {
            foreach ($group['items'] as $key => $definition) {
                $base = match ($definition['type']) {
                    'email' => ['nullable', 'email:rfc', 'max:190'],
                    'url' => ['nullable', 'url', 'max:255'],
                    'tel' => ['nullable', 'string', 'max:40'],
                    'textarea' => ['nullable', 'string', 'max:2000'],
                    'bool' => ['nullable', 'boolean'],
                    default => ['nullable', 'string', 'max:255'],
                };

                if ($definition['translatable'] ?? false) {
                    $rules['settings.'.$key] = ['nullable', 'array'];
                    foreach ($locales as $locale) {
                        $rules['settings.'.$key.'.'.$locale] = $base;
                    }
                } else {
                    $rules['settings.'.$key] = $base;
                }
            }
        }

        return $rules;
    }
}
