<?php

namespace App\Services\SeoAi;

use App\Models\SeoAiSetting;

/**
 * Picks the provider the administrator chose, per generation.
 *
 * Resolved at call time rather than injected once, so switching provider in the
 * admin takes effect on the next run without a deploy or a restart — which is
 * the whole point of having two.
 */
class SeoGeneratorFactory
{
    public const PROVIDERS = ['ollama', 'openai'];

    public function make(?string $provider = null): SeoGenerator
    {
        $provider ??= SeoAiSetting::current()->provider;

        return match ($provider) {
            'openai' => app(OpenAiSeoGenerator::class),
            // Ollama is the default: it is the one that costs nothing, and an
            // unrecognised value must not fall through to the paid provider.
            default => app(OllamaSeoGenerator::class),
        };
    }

    public static function label(string $provider): string
    {
        return match ($provider) {
            'openai' => 'OpenAI (payant, distant)',
            'ollama' => 'Ollama (gratuit, sur ce serveur)',
            default => $provider,
        };
    }
}
