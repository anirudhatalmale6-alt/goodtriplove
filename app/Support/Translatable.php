<?php

namespace App\Support;

/**
 * Translated attributes are stored as JSON maps: {"fr":"…","en":"…"}.
 * A missing locale falls back to the site default, then to the first value
 * present, so a half-translated record still renders instead of showing blanks.
 */
trait Translatable
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        // getAttributeValue(), not getAttribute(): on a model where the column
        // is not loaded (an unsaved `new Place`), getAttribute() falls through
        // to relation resolution and would call a same-named method on this
        // trait — which calls back into here. That recursion exhausts memory
        // and kills the PHP worker outright.
        $value = $this->getAttributeValue($attribute);

        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value) || $value === []) {
            return null;
        }

        $locale ??= app()->getLocale();
        $fallback = config('goodtriplove.default_locale', 'fr');

        foreach ([$locale, $fallback, 'en'] as $candidate) {
            if (! empty($value[$candidate])) {
                return $value[$candidate];
            }
        }

        $first = collect($value)->filter(fn ($v) => filled($v))->first();

        return is_string($first) ? $first : null;
    }

    /**
     * Named displayName(), not name(): a method called name() on a model that
     * also has a `name` column is resolved by Eloquent as a relationship as
     * soon as the attribute is absent, which is how the recursion above starts.
     */
    public function displayName(?string $locale = null): ?string
    {
        return $this->translate('name', $locale);
    }

    public function describe(?string $locale = null): ?string
    {
        return $this->translate('description', $locale);
    }
}
