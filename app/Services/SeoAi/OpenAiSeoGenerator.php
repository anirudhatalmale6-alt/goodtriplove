<?php

namespace App\Services\SeoAi;

use App\Models\SeoAiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generation through the OpenAI Responses API.
 *
 * Kept as an option rather than removed: the local model is free but modest,
 * and the administrator can switch back from the admin screen whenever the
 * quality of a page matters more than its cost.
 */
class OpenAiSeoGenerator extends AbstractSeoGenerator
{
    public function describe(): string
    {
        return 'OpenAI ('.$this->model().')';
    }

    protected function generateLocale(array $context, string $locale): ?array
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::timeout(120)->retry(2, 1500)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => $this->model(),
                'input' => $this->prompt($context, $locale),
                'store' => false,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'OpenAI request failed: HTTP '.$response->status().' '.Str::limit($response->body(), 300)
            );
        }

        return $this->decodeJson($this->extractOutputText($response->json()));
    }

    private function apiKey(): ?string
    {
        // Not env(): the deploy runs `config:cache`, after which env() returns
        // null and this fallback silently stops existing.
        $key = SeoAiSetting::current()->api_key ?: config('seo_ai.openai_api_key');

        return filled($key) ? (string) $key : null;
    }

    private function model(): string
    {
        $configured = SeoAiSetting::current()->model;

        return filled($configured) ? $configured : (string) config('seo_ai.openai_model');
    }

    private function extractOutputText(array $payload): string
    {
        $chunks = [];

        foreach ($payload['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    $chunks[] = $content['text'];
                }
            }
        }

        if (! $chunks) {
            throw new RuntimeException('OpenAI response contained no output text.');
        }

        return implode("\n", $chunks);
    }
}
