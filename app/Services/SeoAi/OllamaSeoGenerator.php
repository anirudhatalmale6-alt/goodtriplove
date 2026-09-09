<?php

namespace App\Services\SeoAi;

use App\Models\SeoAiSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Generation on the server itself, through Ollama. No API key, no per-page cost.
 *
 * The trade is speed: this box has six cores and no GPU, and measured
 * throughput is roughly 14 tokens/second on a 1.7B model, half that on a 4B.
 * That is fine for a job that runs once a week at 04:45 and unusable for
 * anything interactive, which is why the weekly command is the only automatic
 * caller.
 *
 * Two Qwen3 specifics that would otherwise break every run:
 *
 *  - It emits a `<think>` reasoning block before its answer unless told not to.
 *    `think => false` turns it off, and {@see AbstractSeoGenerator::decodeJson}
 *    strips it anyway for models that ignore the flag.
 *  - `num_thread` must be capped. The same machine serves goodtriplove.com and
 *    the other sites; letting inference take all six cores makes every visitor
 *    wait behind it.
 */
class OllamaSeoGenerator extends AbstractSeoGenerator
{
    public function describe(): string
    {
        return 'Ollama ('.$this->model().')';
    }

    protected function generateLocale(array $context, string $locale): ?array
    {
        $response = Http::timeout((int) config('seo_ai.ollama.timeout', 900))
            ->acceptJson()
            ->post($this->endpoint().'/api/generate', [
                'model' => $this->model(),
                'prompt' => $this->prompt($context, $locale),
                'stream' => false,
                // Qwen3 reasons out loud by default; the module wants JSON only.
                'think' => false,
                // Ollama can constrain decoding to valid JSON. This is what
                // makes a small model dependable here rather than hopeful.
                'format' => 'json',
                'options' => [
                    'temperature' => (float) config('seo_ai.ollama.temperature', 0.35),
                    'num_predict' => (int) config('seo_ai.ollama.num_predict', 2400),
                    'num_ctx' => (int) config('seo_ai.ollama.num_ctx', 8192),
                    'num_thread' => (int) config('seo_ai.ollama.num_thread', 4),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Ollama request failed: HTTP '.$response->status().' '.mb_substr($response->body(), 0, 300)
            );
        }

        $text = (string) ($response->json('response') ?? '');

        if ($text === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return $this->decodeJson($text);
    }

    /** The admin's choice wins; the config value is the fallback. */
    private function model(): string
    {
        $configured = SeoAiSetting::current()->ollama_model;

        return filled($configured) ? $configured : (string) config('seo_ai.ollama.model');
    }

    private function endpoint(): string
    {
        $configured = SeoAiSetting::current()->ollama_url;

        return rtrim(filled($configured) ? $configured : (string) config('seo_ai.ollama.url'), '/');
    }

    /**
     * Whether Ollama is reachable and the chosen model is actually pulled.
     *
     * Shown in the admin, because "nothing generated" has two very different
     * causes: the service is down, or the model name is a typo.
     *
     * @return array{reachable: bool, models: array<int, string>, has_model: bool, error: ?string}
     */
    public function status(): array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get($this->endpoint().'/api/tags');

            if (! $response->successful()) {
                return ['reachable' => false, 'models' => [], 'has_model' => false,
                    'error' => 'HTTP '.$response->status()];
            }

            $models = collect($response->json('models') ?? [])
                ->pluck('name')->filter()->values()->all();

            return [
                'reachable' => true,
                'models' => $models,
                // Ollama reports "qwen3:4b"; an admin may well type "qwen3".
                'has_model' => collect($models)->contains(
                    fn ($m) => $m === $this->model() || str_starts_with($m, $this->model().':')
                ),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return ['reachable' => false, 'models' => [], 'has_model' => false,
                'error' => $e->getMessage()];
        }
    }
}
