<?php

namespace App\Services\SeoAi;

/**
 * A source of editorial copy for one SEO page, in every configured locale.
 *
 * Two implementations: OpenAI (paid, remote) and Ollama (free, running on the
 * same box). The publisher does not care which — it asks for translations and
 * gets back the same shape either way, which is what lets the administrator
 * switch provider from a dropdown without a deploy.
 *
 * @return array<string, array> keyed by locale
 */
interface SeoGenerator
{
    public function generate(array $context): array;

    /** Human-readable name of what actually produced the text, for the audit trail. */
    public function describe(): string;
}
