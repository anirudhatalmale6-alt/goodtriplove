<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Local AI on a shared server.
 *
 * This box also serves PauloTrip and CourtierVIP, so the model must never be
 * allowed to take the machine down with it: one inference at a time behind an
 * atomic lock, a small context window, a capped thread count and a hard
 * timeout. Any failure returns null and the caller falls back to heuristics.
 */
class OllamaClient
{
    public function enabled(): bool
    {
        return (bool) config('goodtriplove.ollama.enabled');
    }

    public function isUp(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            return Http::timeout(3)
                ->get(rtrim(config('goodtriplove.ollama.base_url'), '/').'/api/tags')
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Ask the model for a JSON object. Returns null if the model is busy,
     * unreachable, slow or answers with something that is not JSON.
     */
    public function json(string $prompt, string $system = ''): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $lock = Cache::lock('ollama:inference', (int) config('goodtriplove.ollama.lock_seconds'));

        // Wait briefly rather than queueing a second inference on top of the
        // first — two 1.7B models resident at once is what would hurt.
        if (! $lock->block(15, fn () => true)) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('goodtriplove.ollama.timeout'))
                ->post(rtrim(config('goodtriplove.ollama.base_url'), '/').'/api/generate', [
                    'model' => config('goodtriplove.ollama.model'),
                    'prompt' => $prompt,
                    'system' => $system,
                    'stream' => false,
                    'format' => 'json',
                    'keep_alive' => config('goodtriplove.ollama.keep_alive'),
                    'options' => [
                        'temperature' => 0.1,
                        'num_ctx' => (int) config('goodtriplove.ollama.num_ctx'),
                        'num_thread' => (int) config('goodtriplove.ollama.num_thread'),
                        'num_predict' => 400,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Ollama returned HTTP '.$response->status());

                return null;
            }

            $raw = (string) $response->json('response');

            // Qwen3 can prepend a <think> block even with format=json.
            $raw = preg_replace('#<think>.*?</think>#s', '', $raw) ?? $raw;

            if (preg_match('/\{.*\}/s', $raw, $matches)) {
                $raw = $matches[0];
            }

            $decoded = json_decode(trim($raw), true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('Ollama call failed: '.$e->getMessage());

            return null;
        } finally {
            optional($lock)->release();
        }
    }
}
